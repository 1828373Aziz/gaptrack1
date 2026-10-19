import {
  doublePrecision,
  index,
  integer,
  pgTable,
  serial,
  text,
  timestamp,
  uniqueIndex,
} from "drizzle-orm/pg-core";

export const shipmentsTable = pgTable(
  "gaptrack_shipments",
  {
    id: serial("id").primaryKey(),
    trackingCode: text("tracking_code").notNull(),
    origin: text("origin").notNull(),
    destination: text("destination").notNull(),
    transportType: text("transport_type").notNull().default("land"),
    status: text("status").notNull().default("in_transit"),
    createdAt: timestamp("created_at", { withTimezone: true })
      .notNull()
      .defaultNow(),
  },
  (table) => [uniqueIndex("gaptrack_shipments_tracking_code_idx").on(table.trackingCode)],
);

export const trackingPointsTable = pgTable(
  "gaptrack_tracking_points",
  {
    id: serial("id").primaryKey(),
    shipmentId: integer("shipment_id")
      .notNull()
      .references(() => shipmentsTable.id, { onDelete: "cascade" }),
    lat: doublePrecision("lat").notNull(),
    lng: doublePrecision("lng").notNull(),
    timestamp: timestamp("timestamp", { withTimezone: true }).notNull(),
    connectionStatus: text("connection_status").notNull().default("connected"),
  },
  (table) => [
    index("gaptrack_tracking_points_shipment_time_idx").on(
      table.shipmentId,
      table.timestamp,
    ),
  ],
);

export const coverageZonesTable = pgTable("gaptrack_coverage_zones", {
  id: serial("id").primaryKey(),
  name: text("name").notNull(),
  latMin: doublePrecision("lat_min").notNull(),
  latMax: doublePrecision("lat_max").notNull(),
  lngMin: doublePrecision("lng_min").notNull(),
  lngMax: doublePrecision("lng_max").notNull(),
  expectedGapMinutes: integer("expected_gap_minutes").notNull().default(30),
});
