import { randomUUID } from "node:crypto";
import { and, asc, desc, eq } from "drizzle-orm";
import { Router, type IRouter } from "express";
import {
  CreateShipmentBody,
  CreateShipmentResponse,
  CreateZoneBody,
  CreateZoneResponse,
  DeleteZoneParams,
  GetCoverageReportResponse,
  GetDashboardSummaryResponse,
  GetShipmentParams,
  GetShipmentResponse,
  ListShipmentsResponse,
  ListZonesResponse,
} from "@workspace/api-zod";
import {
  coverageZonesTable,
  db,
  shipmentsTable,
  trackingPointsTable,
} from "@workspace/db";

const router: IRouter = Router();

const CITY_COORDINATES: Record<string, [number, number]> = {
  الرياض: [24.7136, 46.6753],
  جدة: [21.4858, 39.1925],
  الدمام: [26.4207, 50.0888],
  "مكة المكرمة": [21.3891, 39.8579],
  "المدينة المنورة": [24.5247, 39.5692],
  الطائف: [21.2703, 40.4158],
  تبوك: [28.3838, 36.555],
  أبها: [18.2164, 42.5053],
  نجران: [17.4933, 44.1277],
  حائل: [27.5114, 41.69],
  بريدة: [26.326, 43.975],
  عنيزة: [26.0843, 43.9936],
};

type ZoneRow = typeof coverageZonesTable.$inferSelect;
type ShipmentRow = typeof shipmentsTable.$inferSelect;
type PointRow = typeof trackingPointsTable.$inferSelect;
type Classification = "connected" | "known_gap" | "abnormal_gap";

function toZone(zone: ZoneRow) {
  return {
    id: zone.id,
    name: zone.name,
    latMin: zone.latMin,
    latMax: zone.latMax,
    lngMin: zone.lngMin,
    lngMax: zone.lngMax,
    expectedGapMinutes: zone.expectedGapMinutes,
  };
}

function zoneAt(lat: number, lng: number, zones: ZoneRow[]) {
  return zones.find(
    (zone) =>
      lat >= zone.latMin &&
      lat <= zone.latMax &&
      lng >= zone.lngMin &&
      lng <= zone.lngMax,
  );
}

function haversineKm(
  lat1: number,
  lng1: number,
  lat2: number,
  lng2: number,
): number {
  const toRadians = (degrees: number) => (degrees * Math.PI) / 180;
  const dLat = toRadians(lat2 - lat1);
  const dLng = toRadians(lng2 - lng1);
  const a =
    Math.sin(dLat / 2) ** 2 +
    Math.cos(toRadians(lat1)) *
      Math.cos(toRadians(lat2)) *
      Math.sin(dLng / 2) ** 2;
  return 6371 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function analyzePoints(points: PointRow[], zones: ZoneRow[]) {
  let previousTime: number | null = null;
  return points.map((point) => {
    const pointTime = point.timestamp.getTime();
    const gapMinutes =
      previousTime === null ? 0 : (pointTime - previousTime) / 60_000;
    const zone = zoneAt(point.lat, point.lng, zones);
    let classification: Classification = "connected";
    let note: string | null = null;

    if (zone) {
      classification = "known_gap";
      note = `منطقة ضعف تغطية معروفة: ${zone.name}`;
    } else if (gapMinutes > 90) {
      classification = "abnormal_gap";
      note = `انقطاع غير متوقع لمدة ${Math.round(gapMinutes)} دقيقة خارج المناطق المسجلة`;
    }

    previousTime = pointTime;
    return { point, classification, note, gapMinutes, zone };
  });
}

function toShipment(
  shipment: ShipmentRow,
  points: PointRow[],
  zones: ZoneRow[],
) {
  const analyzed = analyzePoints(points, zones);
  const lastPoint = points.at(-1);
  const health: Classification = analyzed.some(
    (item) => item.classification === "abnormal_gap",
  )
    ? "abnormal_gap"
    : analyzed.some((item) => item.classification === "known_gap")
      ? "known_gap"
      : "connected";

  return {
    id: shipment.id,
    trackingCode: shipment.trackingCode,
    origin: shipment.origin,
    destination: shipment.destination,
    transportType: shipment.transportType as "land" | "sea",
    status: shipment.status as "in_transit" | "completed",
    createdAt: shipment.createdAt,
    lastUpdate: lastPoint?.timestamp ?? null,
    lastLocationLat: lastPoint?.lat ?? null,
    lastLocationLng: lastPoint?.lng ?? null,
    updateCount: points.length,
    health,
  };
}

async function loadShipmentsWithPoints() {
  const [shipments, points, zones] = await Promise.all([
    db.select().from(shipmentsTable).orderBy(desc(shipmentsTable.createdAt)),
    db.select().from(trackingPointsTable).orderBy(asc(trackingPointsTable.timestamp)),
    db.select().from(coverageZonesTable),
  ]);
  const pointsByShipment = new Map<number, PointRow[]>();
  for (const point of points) {
    const existing = pointsByShipment.get(point.shipmentId) ?? [];
    existing.push(point);
    pointsByShipment.set(point.shipmentId, existing);
  }
  return {
    shipments,
    points,
    zones,
    pointsByShipment,
  };
}

router.get("/dashboard/summary", async (_req, res): Promise<void> => {
  const { shipments, zones, pointsByShipment } = await loadShipmentsWithPoints();
  let knownGaps = 0;
  let abnormalGaps = 0;
  const recentActivity: Array<{
    id: string;
    shipmentId: number;
    trackingCode: string;
    title: string;
    description: string;
    severity: "info" | "warning" | "danger";
    timestamp: Date;
  }> = [];

  for (const shipment of shipments) {
    const analyzed = analyzePoints(pointsByShipment.get(shipment.id) ?? [], zones);
    for (const item of analyzed) {
      if (item.classification === "known_gap") {
        knownGaps += 1;
        recentActivity.push({
          id: `known-${item.point.id}`,
          shipmentId: shipment.id,
          trackingCode: shipment.trackingCode,
          title: "دخول منطقة ضعف تغطية",
          description: item.zone?.name ?? "منطقة مسجلة",
          severity: "warning",
          timestamp: item.point.timestamp,
        });
      } else if (item.classification === "abnormal_gap") {
        abnormalGaps += 1;
        recentActivity.push({
          id: `abnormal-${item.point.id}`,
          shipmentId: shipment.id,
          trackingCode: shipment.trackingCode,
          title: "انقطاع غير متوقع",
          description: item.note ?? "يحتاج إلى متابعة",
          severity: "danger",
          timestamp: item.point.timestamp,
        });
      }
    }
  }

  const result = GetDashboardSummaryResponse.parse({
    totalShipments: shipments.length,
    inTransit: shipments.filter((shipment) => shipment.status === "in_transit")
      .length,
    knownGaps,
    abnormalGaps,
    zoneCount: zones.length,
    affectedPoints: knownGaps + abnormalGaps,
    recentActivity: recentActivity
      .sort((a, b) => b.timestamp.getTime() - a.timestamp.getTime())
      .slice(0, 8),
  });
  res.json(result);
});

router.get("/shipments", async (_req, res): Promise<void> => {
  const { shipments, zones, pointsByShipment } = await loadShipmentsWithPoints();
  res.json(
    ListShipmentsResponse.parse(
      shipments.map((shipment) =>
        toShipment(
          shipment,
          pointsByShipment.get(shipment.id) ?? [],
          zones,
        ),
      ),
    ),
  );
});

router.post("/shipments", async (req, res): Promise<void> => {
  const parsed = CreateShipmentBody.safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.message });
    return;
  }
  const { origin, destination, transportType, pointsCount } = parsed.data;
  if (origin === destination) {
    res.status(400).json({ error: "مدينة الانطلاق والوجهة يجب أن تكونا مختلفتين" });
    return;
  }
  const start = CITY_COORDINATES[origin];
  const end = CITY_COORDINATES[destination];
  if (!start || !end) {
    res.status(400).json({ error: "اختر مدينة سعودية من القائمة" });
    return;
  }

  const [shipment] = await db
    .insert(shipmentsTable)
    .values({
      trackingCode: `GT-${randomUUID().slice(0, 8).toUpperCase()}`,
      origin,
      destination,
      transportType,
      status: "in_transit",
    })
    .returning();
  if (!shipment) {
    res.status(500).json({ error: "تعذر إنشاء الشحنة" });
    return;
  }

  const zones = await db.select().from(coverageZonesTable);
  const now = Date.now();
  const routePoints = Array.from({ length: pointsCount }, (_, index) => {
    const fraction = index / (pointsCount - 1);
    const jitter = index > 0 && index < pointsCount - 1 ? Math.sin(index * 4.3) * 0.014 : 0;
    const lat = start[0] + (end[0] - start[0]) * fraction + jitter;
    const lng = start[1] + (end[1] - start[1]) * fraction - jitter;
    const pointTime = new Date(now - (pointsCount - index - 1) * 60 * 60_000);
    return { lat, lng, timestamp: pointTime };
  });
  const classifiedPoints = routePoints.map((point, index) => {
    const prev = routePoints[index - 1];
    const gapMinutes = prev
      ? (point.timestamp.getTime() - prev.timestamp.getTime()) / 60_000
      : 0;
    const zone = zoneAt(point.lat, point.lng, zones);
    return {
      shipmentId: shipment.id,
      ...point,
      connectionStatus: zone
        ? "known_gap"
        : gapMinutes > 90
          ? "abnormal_gap"
          : "connected",
    };
  });
  const points = await db
    .insert(trackingPointsTable)
    .values(classifiedPoints)
    .returning();
  const result = CreateShipmentResponse.parse(
    toShipment(shipment, points, zones),
  );
  res.status(201).json(result);
});

router.get("/shipments/:id", async (req, res): Promise<void> => {
  const params = GetShipmentParams.safeParse(req.params);
  if (!params.success) {
    res.status(400).json({ error: params.error.message });
    return;
  }
  const [shipment] = await db
    .select()
    .from(shipmentsTable)
    .where(eq(shipmentsTable.id, params.data.id));
  if (!shipment) {
    res.status(404).json({ error: "الشحنة غير موجودة" });
    return;
  }
  const [points, zones] = await Promise.all([
    db
      .select()
      .from(trackingPointsTable)
      .where(eq(trackingPointsTable.shipmentId, shipment.id))
      .orderBy(asc(trackingPointsTable.timestamp)),
    db.select().from(coverageZonesTable),
  ]);
  const analyzed = analyzePoints(points, zones);
  const lastPoint = analyzed.at(-1);
  const predictiveAlert =
    lastPoint?.classification === "connected"
      ? zones
          .map((zone) => ({
            zone,
            distanceKm: haversineKm(
              lastPoint.point.lat,
              lastPoint.point.lng,
              (zone.latMin + zone.latMax) / 2,
              (zone.lngMin + zone.lngMax) / 2,
            ),
          }))
          .sort((a, b) => a.distanceKm - b.distanceKm)
          .filter((item) => item.distanceKm < 150)
          .slice(0, 1)
          .map((item) => ({
            zoneName: item.zone.name,
            distanceKm: Math.round(item.distanceKm),
            etaMinutes: Math.round((item.distanceKm / 70) * 60),
          }))[0] ?? null
      : null;

  const alerts: Array<{
    type: "known_gap" | "abnormal_gap" | "predictive";
    message: string;
    timestamp: Date;
  }> = [];
  for (const item of analyzed) {
    if (item.classification === "known_gap") {
      alerts.push({
        type: "known_gap",
        message: `دخلت الشحنة منطقة «${item.zone?.name}». مدة ضعف التغطية المتوقعة ${item.zone?.expectedGapMinutes ?? 0} دقيقة.`,
        timestamp: item.point.timestamp,
      });
    } else if (item.classification === "abnormal_gap") {
      alerts.push({
        type: "abnormal_gap",
        message: item.note ?? "انقطاع غير متوقع خارج المناطق المعروفة",
        timestamp: item.point.timestamp,
      });
    }
  }
  if (predictiveAlert) {
    alerts.unshift({
      type: "predictive" as const,
      message: `تقترب الشحنة من منطقة «${predictiveAlert.zoneName}» على بعد ${predictiveAlert.distanceKm} كم تقريبًا.`,
      timestamp: lastPoint?.point.timestamp ?? shipment.createdAt,
    });
  }

  const result = GetShipmentResponse.parse({
    shipment: toShipment(shipment, points, zones),
    points: analyzed.map(({ point, classification, note }) => ({
      id: point.id,
      lat: point.lat,
      lng: point.lng,
      timestamp: point.timestamp,
      classification,
      note,
    })),
    alerts,
    predictiveAlert,
  });
  res.json(result);
});

router.get("/zones", async (_req, res): Promise<void> => {
  const zones = await db.select().from(coverageZonesTable).orderBy(desc(coverageZonesTable.id));
  res.json(ListZonesResponse.parse(zones.map(toZone)));
});

router.post("/zones", async (req, res): Promise<void> => {
  const parsed = CreateZoneBody.safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.message });
    return;
  }
  const { name, latMin, latMax, lngMin, lngMax, expectedGapMinutes } =
    parsed.data;
  if (latMin >= latMax || lngMin >= lngMax) {
    res.status(400).json({ error: "يجب أن يكون الحد الأدنى أقل من الحد الأقصى" });
    return;
  }
  const [zone] = await db
    .insert(coverageZonesTable)
    .values({ name, latMin, latMax, lngMin, lngMax, expectedGapMinutes })
    .returning();
  res.status(201).json(CreateZoneResponse.parse(zone ? toZone(zone) : zone));
});

router.delete("/zones/:id", async (req, res): Promise<void> => {
  const params = DeleteZoneParams.safeParse(req.params);
  if (!params.success) {
    res.status(400).json({ error: params.error.message });
    return;
  }
  const deleted = await db
    .delete(coverageZonesTable)
    .where(eq(coverageZonesTable.id, params.data.id))
    .returning({ id: coverageZonesTable.id });
  if (!deleted.length) {
    res.status(404).json({ error: "المنطقة غير موجودة" });
    return;
  }
  res.sendStatus(204);
});

router.get("/reports/coverage", async (_req, res): Promise<void> => {
  const { shipments, points, zones, pointsByShipment } =
    await loadShipmentsWithPoints();
  const shipmentById = new Map(shipments.map((shipment) => [shipment.id, shipment]));
  const analyzedById = new Map<number, ReturnType<typeof analyzePoints>>();
  for (const shipment of shipments) {
    analyzedById.set(
      shipment.id,
      analyzePoints(pointsByShipment.get(shipment.id) ?? [], zones),
    );
  }

  const zoneAnalytics = zones.map((zone) => {
    const hitCount = points.filter(
      (point) =>
        point.lat >= zone.latMin &&
        point.lat <= zone.latMax &&
        point.lng >= zone.lngMin &&
        point.lng <= zone.lngMax,
    ).length;
    return {
      zone: toZone(zone),
      hitCount,
      severity: hitCount >= 5 ? "high" : hitCount > 0 ? "medium" : "low",
    } as const;
  });

  const incidents = [...analyzedById.entries()].flatMap(([shipmentId, items]) => {
    const shipment = shipmentById.get(shipmentId);
    if (!shipment) return [];
    return items
      .filter((item) => item.classification === "abnormal_gap")
      .map((item) => ({
        id: item.point.id,
        shipmentId,
        trackingCode: shipment.trackingCode,
        origin: shipment.origin,
        destination: shipment.destination,
        lat: item.point.lat,
        lng: item.point.lng,
        timestamp: item.point.timestamp,
        gapMinutes: Math.round(item.gapMinutes),
      }));
  });
  const knownGapCount = [...analyzedById.values()]
    .flat()
    .filter((item) => item.classification === "known_gap").length;

  const result = GetCoverageReportResponse.parse({
    totalAffectedPoints: knownGapCount + incidents.length,
    abnormalCount: incidents.length,
    knownGapCount,
    zones: zoneAnalytics,
    incidents: incidents.sort(
      (a, b) => b.timestamp.getTime() - a.timestamp.getTime(),
    ),
  });
  res.json(result);
});

export default router;
