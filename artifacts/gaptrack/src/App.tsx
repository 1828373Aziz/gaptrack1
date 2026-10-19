import { type CSSProperties, type ReactNode, useEffect, useMemo, useState } from 'react';
import { QueryClient, QueryClientProvider, useQueryClient } from '@tanstack/react-query';
import { ErrorBoundary } from '@/components/error-boundary';
import { Toaster } from '@/components/ui/toaster';
import { TooltipProvider } from '@/components/ui/tooltip';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import {
  getGetDashboardSummaryQueryKey, getGetCoverageReportQueryKey, getGetShipmentQueryKey,
  getListShipmentsQueryKey, getListZonesQueryKey, useCreateShipment, useCreateZone,
  useDeleteZone, useGetCoverageReport, useGetDashboardSummary, useGetShipment,
  useHealthCheck, useListShipments, useListZones,
} from '@workspace/api-client-react';
import type { CoverageZone, Shipment, ShipmentHealth, TrackingPoint } from '@workspace/api-client-react';
import {
  Activity, AlertCircle, AlertTriangle, ArrowLeft, ArrowUpLeft, Box,
  Check, ChevronDown, ChevronLeft, ChevronRight, CircleHelp, Clock3, Compass, FileBarChart2,
  Filter, MapPin, Navigation, Plus, Radio, Route, Search, ShieldCheck, Ship, Signal,
  Truck, X, Zap,
} from 'lucide-react';
import { Link, Route as WRoute, Switch, useLocation, useParams } from 'wouter';

const queryClient = new QueryClient({ defaultOptions: { queries: { staleTime: 20_000, retry: 1 } } });
const pageNames: Record<string, string> = {
  '/': 'نظرة عامة', '/shipments': 'الشحنات', '/reports': 'تحليل التغطية', '/zones': 'مناطق التغطية', '/about': 'عن GapTrack',
};
const arabic = new Intl.NumberFormat('ar-SA');
const dateFmt = (value?: string | null) => value ? new Intl.DateTimeFormat('ar-SA', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '—';
const compactDate = (value?: string | null) => value ? new Intl.DateTimeFormat('ar-SA', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }).format(new Date(value)) : '—';

function StatusPill({ health }: { health: ShipmentHealth }) {
  const label = health === 'connected' ? 'متصل' : health === 'known_gap' ? 'فجوة متوقعة' : 'انقطاع مقلق';
  return <span className={`status-pill ${health}`} data-testid={`status-health-${health}`}><i />{label}</span>;
}
function PageTitle({ eyebrow, title, subtitle, actions }: { eyebrow: string; title: string; subtitle: string; actions?: ReactNode }) {
  return <div className="page-title"><div><p className="eyebrow">{eyebrow}</p><h1>{title}</h1><p className="page-subtitle">{subtitle}</p></div>{actions && <div className="title-actions">{actions}</div>}</div>;
}
function ErrorState({ retry, label = 'تعذّر تحميل البيانات' }: { retry: () => void; label?: string }) {
  return <div className="state-card error-state" data-testid="status-error"><span className="state-icon"><AlertCircle size={22} /></span><strong>{label}</strong><p>تحقق من الاتصال ثم أعد المحاولة.</p><button className="button button-soft" onClick={retry} data-testid="button-retry">إعادة المحاولة</button></div>;
}
function SkeletonRows({ rows = 4 }: { rows?: number }) {
  return <div className="skeleton-stack" aria-label="جارٍ التحميل">{Array.from({ length: rows }, (_, index) => <div className="skeleton-row" key={index}><i /><span /><span /><b /></div>)}</div>;
}
function EmptyState({ title, body, action }: { title: string; body: string; action?: ReactNode }) {
  return <div className="state-card empty-state" data-testid="state-empty"><span className="state-icon"><Compass size={22} /></span><strong>{title}</strong><p>{body}</p>{action}</div>;
}

function Shell({ children }: { children: ReactNode }) {
  const [location] = useLocation();
  const health = useHealthCheck();
  const title = location.startsWith('/shipments/') ? 'تفاصيل الشحنة' : pageNames[location] || 'GapTrack';
  const nav = [
    { href: '/', label: 'نظرة عامة', icon: Activity },
    { href: '/shipments', label: 'الشحنات', icon: Box },
    { href: '/reports', label: 'تحليل التغطية', icon: FileBarChart2 },
    { href: '/zones', label: 'مناطق التغطية', icon: MapPin },
    { href: '/about', label: 'عن GapTrack', icon: CircleHelp },
  ];
  return <div className="app-shell" dir="rtl">
    <aside className="sidebar">
      <Link href="/" className="brand" data-testid="link-brand"><span className="brand-mark"><Route size={22} /></span><span><b>GapTrack</b><small>وضوحٌ لكل رحلة</small></span></Link>
      <div className="workspace-chip"><span className="workspace-dot" /><span>عمليات المملكة</span><ChevronDown size={14} /></div>
      <div className="nav-caption">مساحة العمل</div>
      <nav className="side-nav" aria-label="التنقل الرئيسي">{nav.map(({ href, label, icon: Icon }) => <Link key={href} href={href} className={`nav-link ${location === href || (href === '/shipments' && location.startsWith('/shipments/')) ? 'active' : ''}`} data-testid={`link-nav-${href === '/' ? 'home' : href.slice(1)}`}><Icon size={18} /><span>{label}</span>{href === '/shipments' && <ChevronLeft className="nav-chevron" size={15} />}</Link>)}</nav>
      <div className="sidebar-bottom">
        <div className="signal-card"><div className="signal-card-top"><span className={`signal-dot ${health.isError ? 'offline' : ''}`} /><span>حالة النظام</span></div><strong data-testid="status-system-health">{health.isLoading ? 'جارٍ فحص الأنظمة' : health.isError ? 'تعذّر الاتصال بالخدمة' : 'جميع الأنظمة تعمل'}</strong><small>{health.data?.status || (health.isError ? 'تحقق من الاتصال' : 'آخر فحص قبل لحظات')}</small></div>
        <div className="profile"><div className="avatar">ع</div><div><b>فريق العمليات</b><small>مركز التحكم</small></div><ChevronDown size={15} /></div>
      </div>
    </aside>
    <div className="main-column">
      <header className="topbar"><div className="breadcrumbs"><span>GapTrack</span><ChevronLeft size={14} /><strong>{title}</strong></div><div className="topbar-right"><span className="live-indicator"><i /> مباشر</span><span className="topbar-date">{new Intl.DateTimeFormat('ar-SA', { dateStyle: 'full' }).format(new Date())}</span><span className="topbar-avatar">ع</span></div></header>
      <main className="main-content">{children}</main>
      <footer className="footer"><span>GapTrack <b>·</b> وضوح الإشارة، قرار أفضل</span><span>مصمم لفرق الخدمات اللوجستية في المملكة</span></footer>
    </div>
    <nav className="mobile-nav" aria-label="التنقل">{nav.slice(0, 4).map(({ href, label, icon: Icon }) => <Link key={href} href={href} className={location === href ? 'selected' : ''} data-testid={`mobile-nav-${href.slice(1) || 'home'}`}><Icon size={19} /><span>{label}</span></Link>)}</nav>
  </div>;
}

function Metric({ label, value, note, icon: Icon, tone = '' }: { label: string; value: number; note: string; icon: typeof Activity; tone?: string }) {
  return <div className={`metric-card ${tone}`} data-testid={`metric-${label}`}><div className="metric-head"><span>{label}</span><span className="metric-icon"><Icon size={18} /></span></div><div className="metric-number" data-testid={`value-${label}`}>{arabic.format(value)}</div><div className="metric-note">{note}</div></div>;
}

function HomePage() {
  const summary = useGetDashboardSummary();
  const shipments = useListShipments();
  return <div className="page-wrap">
    <PageTitle eyebrow="مركز المتابعة · اليوم" title="صباح الخير، فريق العمليات" subtitle="صورة واضحة لحالة الإشارة على طرق المملكة." actions={<Link href="/shipments" className="button button-primary" data-testid="button-view-shipments"><Box size={17} /> جميع الشحنات <ArrowLeft size={16} /></Link>} />
    {summary.isLoading ? <div className="metrics-grid">{[1, 2, 3, 4].map((i) => <div className="metric-skeleton" key={i} />)}</div> : summary.isError || !summary.data ? <ErrorState retry={() => summary.refetch()} /> : <>
      <div className="metrics-grid">
        <Metric label="إجمالي الشحنات" value={summary.data.totalShipments} note="ضمن نطاق المتابعة" icon={Box} />
        <Metric label="في الطريق" value={summary.data.inTransit} note="رحلات نشطة الآن" icon={Navigation} tone="metric-green" />
        <Metric label="فجوات متوقعة" value={summary.data.knownGaps} note="ضمن مناطق التغطية المعروفة" icon={Signal} tone="metric-amber" />
        <Metric label="انقطاعات مقلقة" value={summary.data.abnormalGaps} note="تحتاج إلى مراجعة" icon={AlertTriangle} tone="metric-red" />
      </div>
      <section className="overview-band">
        <div className="overview-copy"><span className="overview-icon"><ShieldCheck size={20} /></span><div><b>الإشارة تنقطع أحياناً. ليس كل انقطاع مشكلة.</b><p>نميّز بين فجوات التغطية المتوقعة وبين الصمت الذي يستحق انتباهك.</p></div></div>
        <div className="overview-stat"><b>{arabic.format(summary.data.zoneCount)}</b><span>منطقة تغطية مسجلة</span></div>
        <div className="overview-stat"><b>{arabic.format(summary.data.affectedPoints)}</b><span>نقطة متأثرة</span></div>
        <Link href="/about" className="overview-link" data-testid="link-learn-about">كيف نعمل؟ <ArrowLeft size={15} /></Link>
      </section>
      <div className="content-grid">
        <section className="panel activity-panel">
          <div className="panel-heading"><div><p className="eyebrow">آخر المستجدات</p><h2>نشاط الرحلات</h2></div><Link href="/shipments" className="text-link" data-testid="link-all-activity">عرض الشحنات <ArrowLeft size={15} /></Link></div>
          {summary.data.recentActivity.length ? <div className="activity-list">{summary.data.recentActivity.map((item) => <Link href={`/shipments/${item.shipmentId}`} className="activity-row" key={item.id} data-testid={`activity-${item.id}`}><span className={`activity-marker ${item.severity}`}><i /></span><span className="activity-main"><b>{item.title}</b><small>{item.description}</small></span><span className="activity-code">{item.trackingCode}</span><time>{compactDate(item.timestamp)}</time><ChevronLeft size={15} /></Link>)}</div> : <EmptyState title="لا يوجد نشاط بعد" body="ستظهر هنا آخر تحديثات الشحنات والتنبيهات." /> }
        </section>
        <section className="panel fleet-panel">
          <div className="panel-heading"><div><p className="eyebrow">لقطة الأسطول</p><h2>حالة الشحنات</h2></div><span className="mini-live"><i /> الآن</span></div>
          {shipments.isLoading ? <SkeletonRows rows={3} /> : shipments.isError ? <ErrorState retry={() => shipments.refetch()} /> : <div className="fleet-list">
            {(shipments.data || []).slice(0, 5).map((s) => <Link href={`/shipments/${s.id}`} className="fleet-row" key={s.id} data-testid={`fleet-shipment-${s.id}`}><span className="fleet-transport">{s.transportType === 'land' ? <Truck size={17} /> : <Ship size={17} />}</span><span className="fleet-text"><b>{s.origin} <ArrowLeft size={12} /> {s.destination}</b><small className="mono">{s.trackingCode}</small></span><StatusPill health={s.health} /></Link>)}
            {!shipments.data?.length && <EmptyState title="لا توجد شحنات" body="أضف أول شحنة لبدء متابعة الإشارة." action={<Link href="/shipments" className="text-link" data-testid="link-create-first">إضافة شحنة <ArrowLeft size={15} /></Link>} />}
          </div>}
          <Link href="/reports" className="fleet-foot" data-testid="link-reports">استكشف جودة التغطية <ArrowLeft size={15} /></Link>
        </section>
      </div>
    </>}
  </div>;
}

const shipmentSchema = z.object({
  origin: z.string().trim().min(1, 'اكتب نقطة الانطلاق'),
  destination: z.string().trim().min(1, 'اكتب الوجهة'),
  transportType: z.enum(['land', 'sea']),
  pointsCount: z.coerce.number().min(3, 'الحد الأدنى 3 نقاط').max(10, 'الحد الأعلى 10 نقاط'),
});
type ShipmentForm = z.infer<typeof shipmentSchema>;
function ShipmentCreateDialog({ onClose }: { onClose: () => void }) {
  const qc = useQueryClient();
  const [, setLocation] = useLocation();
  const mutation = useCreateShipment();
  const form = useForm<ShipmentForm>({ resolver: zodResolver(shipmentSchema), defaultValues: { origin: '', destination: '', transportType: 'land', pointsCount: 6 } });
  const submit = form.handleSubmit((values) => mutation.mutate({ data: values }, { onSuccess: async (s) => { await Promise.all([qc.invalidateQueries({ queryKey: getListShipmentsQueryKey() }), qc.invalidateQueries({ queryKey: getGetDashboardSummaryQueryKey() })]); onClose(); setLocation(`/shipments/${s.id}`); } }));
  return <div className="modal-backdrop" role="presentation" onMouseDown={(e) => { if (e.target === e.currentTarget) onClose(); }}><section className="dialog" role="dialog" aria-modal="true" aria-labelledby="create-title"><div className="dialog-head"><div><p className="eyebrow">رحلة جديدة</p><h2 id="create-title">إضافة شحنة للمتابعة</h2></div><button className="icon-button" onClick={onClose} aria-label="إغلاق" data-testid="button-close-create"><X size={19} /></button></div><p className="dialog-intro">أنشئ مساراً تجريبياً، وسنوضح لك أين تنقطع الإشارة ومتى يستحق الأمر الانتباه.</p>
    <Form {...form}><form onSubmit={submit} className="form-stack">
      <div className="form-two"><FormField control={form.control} name="origin" render={({ field }) => <FormItem><FormLabel>نقطة الانطلاق</FormLabel><FormControl><input {...field} className="field" placeholder="مثال: الرياض" data-testid="input-shipment-origin" /></FormControl><FormMessage /></FormItem>} /><FormField control={form.control} name="destination" render={({ field }) => <FormItem><FormLabel>الوجهة</FormLabel><FormControl><input {...field} className="field" placeholder="مثال: جدة" data-testid="input-shipment-destination" /></FormControl><FormMessage /></FormItem>} /></div>
      <FormField control={form.control} name="transportType" render={({ field }) => <FormItem><FormLabel>وسيلة النقل</FormLabel><div className="choice-row"><button type="button" onClick={() => field.onChange('land')} className={`choice ${field.value === 'land' ? 'chosen' : ''}`} data-testid="option-transport-land"><Truck size={18} /> بري</button><button type="button" onClick={() => field.onChange('sea')} className={`choice ${field.value === 'sea' ? 'chosen' : ''}`} data-testid="option-transport-sea"><Ship size={18} /> بحري</button></div><FormMessage /></FormItem>} />
      <FormField control={form.control} name="pointsCount" render={({ field }) => <FormItem><FormLabel>عدد نقاط المسار <small className="label-hint">من ٣ إلى ١٠</small></FormLabel><FormControl><input {...field} type="number" min="3" max="10" className="field short-field" data-testid="input-shipment-points" /></FormControl><FormMessage /></FormItem>} />
      {mutation.isError && <p className="form-error" data-testid="error-create-shipment">تعذرت إضافة الشحنة. حاول مرة أخرى.</p>}
      <div className="dialog-actions"><button type="button" className="button button-ghost" onClick={onClose} data-testid="button-cancel-create">إلغاء</button><button className="button button-primary" type="submit" disabled={mutation.isPending} data-testid="button-submit-shipment">{mutation.isPending ? 'جارٍ الإنشاء...' : <><Plus size={16} /> إنشاء الشحنة</>}</button></div>
    </form></Form>
  </section></div>;
}

function ShipmentsPage() {
  const [query, setQuery] = useState('');
  const [healthFilter, setHealthFilter] = useState('all');
  const [transportFilter, setTransportFilter] = useState('all');
  const [createOpen, setCreateOpen] = useState(false);
  const result = useListShipments();
  const filtered = useMemo(() => (result.data || []).filter((s) => {
    const text = `${s.trackingCode} ${s.origin} ${s.destination}`.toLocaleLowerCase();
    return text.includes(query.toLocaleLowerCase()) && (healthFilter === 'all' || s.health === healthFilter) && (transportFilter === 'all' || s.transportType === transportFilter);
  }), [result.data, query, healthFilter, transportFilter]);
  return <div className="page-wrap">
    <PageTitle eyebrow="إدارة الرحلات" title="الشحنات" subtitle="تابع كل مسار، وافهم سياق انقطاع الإشارة قبل اتخاذ القرار." actions={<button className="button button-primary" onClick={() => setCreateOpen(true)} data-testid="button-create-shipment"><Plus size={17} /> إضافة شحنة</button>} />
    <section className="panel table-panel">
      <div className="filterbar"><label className="search-field"><Search size={17} /><input value={query} onChange={(e) => setQuery(e.target.value)} placeholder="ابحث برقم التتبع أو المسار..." data-testid="input-search-shipments" /><kbd>⌘ K</kbd></label>
        <label className="filter-select"><Filter size={15} /><select value={healthFilter} onChange={(e) => setHealthFilter(e.target.value)} aria-label="تصفية حسب حالة الإشارة" data-testid="filter-health"><option value="all">كل الحالات</option><option value="connected">متصل</option><option value="known_gap">فجوة متوقعة</option><option value="abnormal_gap">انقطاع مقلق</option></select><ChevronDown size={14} /></label>
        <label className="filter-select"><select value={transportFilter} onChange={(e) => setTransportFilter(e.target.value)} aria-label="تصفية وسيلة النقل" data-testid="filter-transport"><option value="all">كل وسائل النقل</option><option value="land">بري</option><option value="sea">بحري</option></select><ChevronDown size={14} /></label>
        <span className="result-count" data-testid="text-shipment-count">{arabic.format(filtered.length)} شحنة</span>
      </div>
      {result.isLoading ? <SkeletonRows rows={5} /> : result.isError ? <ErrorState retry={() => result.refetch()} /> : filtered.length ? <div className="table-scroll"><table className="data-table"><thead><tr><th>رقم التتبع</th><th>المسار</th><th>النقل</th><th>حالة الإشارة</th><th>آخر تحديث</th><th>نقاط</th><th /></tr></thead><tbody>{filtered.map((s) => <tr key={s.id} data-testid={`row-shipment-${s.id}`}><td><Link href={`/shipments/${s.id}`} className="tracking-code mono" data-testid={`link-shipment-${s.id}`}>{s.trackingCode}</Link><small className="row-sub">بدأت {compactDate(s.createdAt)}</small></td><td><span className="route-cell">{s.origin}<ArrowLeft size={13} />{s.destination}</span></td><td><span className="transport-cell">{s.transportType === 'land' ? <Truck size={15} /> : <Ship size={15} />}{s.transportType === 'land' ? 'بري' : 'بحري'}</span></td><td><StatusPill health={s.health} /></td><td className="date-cell">{compactDate(s.lastUpdate)}</td><td className="number-cell">{arabic.format(s.updateCount)}</td><td><Link href={`/shipments/${s.id}`} className="row-open" aria-label={`تفاصيل ${s.trackingCode}`} data-testid={`button-open-shipment-${s.id}`}><ChevronLeft size={18} /></Link></td></tr>)}</tbody></table></div> : <EmptyState title={query || healthFilter !== 'all' || transportFilter !== 'all' ? 'لا توجد نتائج مطابقة' : 'لا توجد شحنات بعد'} body={query || healthFilter !== 'all' || transportFilter !== 'all' ? 'جرّب تغيير كلمة البحث أو عوامل التصفية.' : 'أنشئ شحنة لبدء مراقبة مسارها.'} action={!query && healthFilter === 'all' && <button className="button button-primary" onClick={() => setCreateOpen(true)} data-testid="button-empty-create"><Plus size={16} /> إضافة شحنة</button>} />}
    </section>
    {createOpen && <ShipmentCreateDialog onClose={() => setCreateOpen(false)} />}
  </div>;
}

function RouteMap({ points, activeIndex = -1 }: { points: TrackingPoint[]; activeIndex?: number }) {
  const coords = points;
  const x = (lng: number) => 12 + ((lng - 34) / 18) * 76;
  const y = (lat: number) => 12 + ((33 - lat) / 18) * 76;
  const d = coords.map((point, i) => `${i === 0 ? 'M' : 'L'} ${x(point.lng)} ${y(point.lat)}`).join(' ');
  return <div className="map-visual" data-testid="route-map"><div className="map-grid" /><span className="map-label map-north">شمال</span><span className="map-label map-west">البحر الأحمر</span><span className="map-label map-east">الخليج العربي</span><span className="map-label city riyadh">الرياض</span><span className="map-label city jeddah">جدة</span><span className="map-label city dammam">الدمام</span><svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-label="رسم مسار الرحلة" className="route-svg"><path className="route-shadow" d={d} /><path className="route-path" d={d} />{coords.map((p, i) => <g key={p.id} className={i === activeIndex ? 'point-active' : ''}><circle cx={x(p.lng)} cy={y(p.lat)} r={i === activeIndex ? 3.2 : 2.25} className={`map-point ${p.classification}`} /><circle cx={x(p.lng)} cy={y(p.lat)} r={i === activeIndex ? 5.3 : 0} className="point-halo" /></g>)}</svg><div className="map-legend"><span><i className="legend-connected" /> متصل</span><span><i className="legend-known" /> فجوة متوقعة</span><span><i className="legend-abnormal" /> انقطاع مقلق</span></div></div>;
}
function ShipmentDetailPage() {
  const { id } = useParams<{ id: string }>();
  const shipmentId = Number(id);
  const detail = useGetShipment(shipmentId, { query: { enabled: Number.isFinite(shipmentId) && shipmentId > 0, queryKey: getGetShipmentQueryKey(shipmentId) } });
  const [active, setActive] = useState(0);
  const [playing, setPlaying] = useState(false);
  const points = detail.data?.points || [];
  const activePoint = points[active];
  useEffect(() => {
    if (!playing || points.length < 2) return;
    const timer = window.setInterval(() => setActive((index) => (index + 1) % points.length), 1200);
    return () => window.clearInterval(timer);
  }, [playing, points.length]);
  return <div className="page-wrap">
    {detail.isLoading ? <><div className="detail-skeleton" /><div className="metric-skeleton" /></> : detail.isError || !detail.data ? <ErrorState retry={() => detail.refetch()} label="لم نتمكن من العثور على هذه الشحنة" /> : <>
      <div className="detail-back"><Link href="/shipments" className="back-link" data-testid="link-back-shipments"><ArrowRightIcon /> العودة إلى الشحنات</Link><span className="mono">{detail.data.shipment.trackingCode}</span></div>
      <PageTitle eyebrow="ملف الرحلة" title={`${detail.data.shipment.origin} إلى ${detail.data.shipment.destination}`} subtitle={`أنشئت ${dateFmt(detail.data.shipment.createdAt)} · ${detail.data.shipment.transportType === 'land' ? 'نقل بري' : 'نقل بحري'}`} actions={<StatusPill health={detail.data.shipment.health} />} />
      {detail.data.predictiveAlert && <div className="predictive-banner"><span className="predictive-symbol"><Zap size={19} /></span><div><b>تنبيه استباقي: اقتراب من منطقة تغطية ضعيفة</b><p>تبعد {arabic.format(detail.data.predictiveAlert.distanceKm)} كم عن {detail.data.predictiveAlert.zoneName} · وصول متوقع خلال {arabic.format(detail.data.predictiveAlert.etaMinutes)} دقيقة.</p></div><span className="predictive-tag">متوقع</span></div>}
      <div className="detail-grid">
        <section className="panel map-panel"><div className="panel-heading"><div><p className="eyebrow">الموقع والمسار</p><h2>رحلة الشحنة</h2></div><span className="updated-tag"><i /> {detail.data.shipment.lastUpdate ? `آخر إشارة ${compactDate(detail.data.shipment.lastUpdate)}` : 'بانتظار أول إشارة'}</span></div>
          <RouteMap points={points} activeIndex={active} />
          <div className="playback"><button className="play-button" onClick={() => { setPlaying(!playing); if (!playing && points.length) setActive((active + 1) % points.length); }} disabled={points.length < 2} data-testid="button-playback"><span>{playing ? 'إيقاف' : 'تشغيل المسار'}</span>{playing ? <span className="pause-symbol">Ⅱ</span> : <Navigation size={16} />}</button><input type="range" min={0} max={Math.max(points.length - 1, 0)} value={active} onChange={(e) => setActive(Number(e.target.value))} disabled={!points.length} aria-label="موضع تشغيل المسار" data-testid="slider-route-playback" /><span className="playback-count">{points.length ? `${arabic.format(active + 1)} / ${arabic.format(points.length)}` : 'لا توجد نقاط'}</span><button className="small-icon-btn" onClick={() => setActive(Math.max(0, active - 1))} aria-label="النقطة السابقة" disabled={active <= 0} data-testid="button-point-prev"><ChevronRight size={17} /></button><button className="small-icon-btn" onClick={() => setActive(Math.min(points.length - 1, active + 1))} aria-label="النقطة التالية" disabled={active >= points.length - 1} data-testid="button-point-next"><ChevronLeft size={17} /></button></div>
          {activePoint && <div className="active-location" data-testid="text-active-location"><MapPin size={15} /><span>{activePoint.note || 'نقطة مسجلة على المسار'}</span><code>{activePoint.lat.toFixed(4)}, {activePoint.lng.toFixed(4)}</code><time>{compactDate(activePoint.timestamp)}</time></div>}
        </section>
        <section className="panel alert-panel"><div className="panel-heading"><div><p className="eyebrow">قراءة الإشارة</p><h2>التنبيهات</h2></div><span className="alert-count">{arabic.format(detail.data.alerts.length)}</span></div>
          {detail.data.alerts.length ? <div className="alert-list">{detail.data.alerts.map((alert, i) => <article className={`alert-item ${alert.type}`} key={`${alert.timestamp}-${i}`} data-testid={`alert-${i}`}><span className="alert-icon">{alert.type === 'known_gap' ? <Signal size={17} /> : alert.type === 'predictive' ? <Zap size={17} /> : <AlertTriangle size={17} />}</span><div><b>{alert.type === 'known_gap' ? 'فجوة متوقعة' : alert.type === 'predictive' ? 'تنبيه استباقي' : 'انقطاع مقلق'}</b><p>{alert.message}</p><time>{dateFmt(alert.timestamp)}</time></div></article>)}</div> : <EmptyState title="لا توجد تنبيهات" body="حالة الإشارة مستقرة حتى الآن." />}
        </section>
      </div>
      <section className="panel points-panel"><div className="panel-heading"><div><p className="eyebrow">سجل المواقع</p><h2>نقاط التتبع <span className="heading-count">{arabic.format(points.length)}</span></h2></div><span className="table-note"><Radio size={14} /> التحديثات المسجلة</span></div>
        {points.length ? <div className="table-scroll"><table className="data-table points-table"><thead><tr><th>التصنيف</th><th>الموقع</th><th>الملاحظة</th><th>التوقيت</th><th /></tr></thead><tbody>{points.map((point, i) => <tr key={point.id} data-testid={`row-point-${point.id}`}><td><StatusPill health={point.classification} /></td><td className="mono coordinate">{point.lat.toFixed(4)}, {point.lng.toFixed(4)}</td><td>{point.note || '—'}</td><td>{compactDate(point.timestamp)}</td><td><button className="row-open" onClick={() => setActive(i)} aria-label="عرض النقطة على المسار" data-testid={`button-focus-point-${point.id}`}><MapPin size={16} /></button></td></tr>)}</tbody></table></div> : <EmptyState title="لا توجد نقاط تتبع بعد" body="ستظهر هنا إحداثيات الرحلة عند تسجيلها." />}
      </section>
    </>}
  </div>;
}
function ArrowRightIcon() { return <ArrowUpLeft size={16} className="back-arrow" />; }

const SAUDI_OUTLINE: Array<[number, number]> = [
  [32.1, 38.8], [30.7, 39.0], [29.5, 38.2], [28.3, 36.7], [26.3, 36.3],
  [24.1, 37.2], [22.4, 38.0], [21.0, 38.5], [19.4, 39.4], [18.2, 40.6],
  [17.3, 41.7], [16.4, 42.4], [16.5, 44.1], [17.0, 45.5], [17.2, 47.4],
  [18.1, 48.4], [18.7, 49.4], [20.1, 50.6], [21.4, 52.5], [22.5, 54.0],
  [23.7, 55.0], [24.5, 54.8], [25.4, 52.7], [26.2, 50.9], [27.1, 49.9],
  [28.5, 49.4], [29.5, 48.5], [30.0, 47.7], [29.3, 46.5], [29.0, 44.4],
  [28.3, 42.7], [28.4, 40.7], [29.5, 39.5],
];

function geoToSvg(lat: number, lng: number) {
  return {
    x: 42 + ((lng - 34) / 22) * 870,
    y: 30 + ((33 - lat) / 18) * 520,
  };
}

function ReportsPage() {
  const report = useGetCoverageReport();
  const severityArabic = (severity: string) => severity === 'high' ? 'مرتفعة' : severity === 'medium' ? 'متوسطة' : 'منخفضة';
  const outlinePoints = SAUDI_OUTLINE.map(([lat, lng]) => {
    const point = geoToSvg(lat, lng);
    return `${point.x},${point.y}`;
  }).join(' ');
  return <div className="page-wrap"><PageTitle eyebrow="رؤية تشغيلية" title="تحليل التغطية" subtitle="اعرف أين تتكرر فجوات الإشارة، وما الذي يستحق المتابعة." actions={<span className="report-period"><Clock3 size={15} /> منذ بدء التتبع</span>} />
    {report.isLoading ? <><div className="metrics-grid"><div className="metric-skeleton" /><div className="metric-skeleton" /><div className="metric-skeleton" /></div><div className="panel metric-skeleton chart-skeleton" /></> : report.isError || !report.data ? <ErrorState retry={() => report.refetch()} /> : <>
      <div className="report-kpis"><div className="report-kpi kpi-ink"><span className="kpi-label">النقاط المتأثرة</span><b>{arabic.format(report.data.totalAffectedPoints)}</b><small>نقاط فقدت الاتصال أثناء الرحلة</small><div className="kpi-decoration"><Activity size={52} /></div></div><div className="report-kpi"><span className="kpi-label">فجوات متوقعة</span><b>{arabic.format(report.data.knownGapCount)}</b><small>انقطاعات ضمن مناطق معروفة</small><div className="kpi-bar"><i style={{ width: report.data.totalAffectedPoints ? `${(report.data.knownGapCount / report.data.totalAffectedPoints) * 100}%` : '0%' }} /></div></div><div className="report-kpi kpi-warning"><span className="kpi-label">انقطاعات مقلقة</span><b>{arabic.format(report.data.abnormalCount)}</b><small>تحتاج إلى مراجعة تشغيلية</small><div className="kpi-bar"><i style={{ width: report.data.totalAffectedPoints ? `${(report.data.abnormalCount / report.data.totalAffectedPoints) * 100}%` : '0%' }} /></div></div></div>
      <section className="panel heatmap-panel" data-testid="coverage-heatmap">
        <div className="panel-heading"><div><p className="eyebrow">التوزيع الجغرافي</p><h2>خريطة حرارية لمناطق الانقطاع</h2></div><span className="table-note"><MapPin size={14} /> المملكة العربية السعودية</span></div>
        <p className="heatmap-description">تركيز اللون يوضح تكرار نقاط ضعف الإشارة المسجلة، وتظهر الانقطاعات غير المتوقعة باللون الأحمر.</p>
        {report.data.totalAffectedPoints ? <svg className="coverage-map-svg" viewBox="0 0 960 600" role="img" aria-label="خريطة حرارية جغرافية لمناطق ضعف التغطية والانقطاعات غير المتوقعة">
          <defs>
            <radialGradient id="heat-known"><stop offset="0%" stopColor="#d3a340" stopOpacity=".78" /><stop offset="42%" stopColor="#e4bd68" stopOpacity=".48" /><stop offset="100%" stopColor="#efcf87" stopOpacity="0" /></radialGradient>
            <radialGradient id="heat-abnormal"><stop offset="0%" stopColor="#bd5148" stopOpacity=".82" /><stop offset="42%" stopColor="#d77961" stopOpacity=".5" /><stop offset="100%" stopColor="#e8a18d" stopOpacity="0" /></radialGradient>
          </defs>
          <rect x="0" y="0" width="960" height="600" rx="10" fill="#eef0e7" />
          {[0, 1, 2, 3, 4].map((line) => <g key={`grid-${line}`}><line x1={42 + line * 217.5} y1="30" x2={42 + line * 217.5} y2="550" stroke="#d9dfd2" strokeWidth="1" /><line x1="42" y1={30 + line * 130} x2="912" y2={30 + line * 130} stroke="#d9dfd2" strokeWidth="1" /></g>)}
          <polygon points={outlinePoints} fill="#e3e9dc" stroke="#b8c4b4" strokeWidth="2" strokeLinejoin="round" />
          {report.data.zones.filter((item) => item.hitCount > 0).map((item) => {
            const center = geoToSvg((item.zone.latMin + item.zone.latMax) / 2, (item.zone.lngMin + item.zone.lngMax) / 2);
            const radius = Math.min(112, 58 + item.hitCount * 12);
            return <g key={`heat-zone-${item.zone.id}`} data-testid={`heat-zone-${item.zone.id}`}><title>{`${item.zone.name}: ${arabic.format(item.hitCount)} نقطة متأثرة`}</title><circle cx={center.x} cy={center.y} r={radius} fill="url(#heat-known)" /><circle cx={center.x} cy={center.y} r="5" fill="#bd8c2e" stroke="#fffaf0" strokeWidth="2" /></g>;
          })}
          {report.data.incidents.map((incident) => {
            const point = geoToSvg(incident.lat, incident.lng);
            return <g key={`heat-incident-${incident.id}`} data-testid={`heat-incident-${incident.id}`}><title>{`${incident.trackingCode}: انقطاع ${arabic.format(incident.gapMinutes)} دقيقة`}</title><circle cx={point.x} cy={point.y} r="55" fill="url(#heat-abnormal)" /><circle cx={point.x} cy={point.y} r="5" fill="#b74840" stroke="#fffaf0" strokeWidth="2" /></g>;
          })}
          {[
            { name: 'تبوك', lat: 28.3838, lng: 36.555 },
            { name: 'حائل', lat: 27.5114, lng: 41.69 },
            { name: 'الرياض', lat: 24.7136, lng: 46.6753 },
            { name: 'جدة', lat: 21.4858, lng: 39.1925 },
            { name: 'الدمام', lat: 26.4207, lng: 50.0888 },
            { name: 'نجران', lat: 17.4933, lng: 44.1277 },
          ].map((city) => {
            const point = geoToSvg(city.lat, city.lng);
            return <g key={city.name}><circle cx={point.x} cy={point.y} r="2.3" fill="#577264" /><text x={point.x + 8} y={point.y - 7} className="map-city-label">{city.name}</text></g>;
          })}
        </svg> : <EmptyState title="لا توجد نقاط انقطاع بعد" body="ستظهر الخريطة بعد تسجيل نقاط تتبع متأثرة." />}
        <div className="heatmap-legend"><span><i className="known-dot" /> منطقة ضعف معروفة</span><span><i className="abnormal-dot" /> انقطاع غير متوقع</span><small>البيانات الحالية تجريبية</small></div>
      </section>
      <div className="content-grid report-grid">
        <section className="panel zones-analytics"><div className="panel-heading"><div><p className="eyebrow">تأثير المناطق</p><h2>أكثر المناطق تكراراً</h2></div><Link href="/zones" className="text-link" data-testid="link-manage-zones">إدارة المناطق <ArrowLeft size={15} /></Link></div>
          {report.data.zones.length ? <div className="zone-analytics-list">{report.data.zones.map((item, i) => <div className="zone-analytics-row" key={item.zone.id} data-testid={`analytics-zone-${item.zone.id}`}><span className="zone-rank">{arabic.format(i + 1)}</span><div className="zone-name-wrap"><b>{item.zone.name}</b><span className={`severity-tag ${item.severity}`}>{severityArabic(item.severity)} التأثير</span></div><div className="zone-bar"><i className={item.severity} style={{ width: `${Math.max(5, (item.hitCount / Math.max(...report.data.zones.map((z) => z.hitCount), 1)) * 100)}%` }} /></div><b className="zone-hits">{arabic.format(item.hitCount)} <small>نقطة</small></b></div>)}</div> : <EmptyState title="لا توجد بيانات تغطية بعد" body="ستظهر المناطق الأكثر تأثيراً بعد تسجيل نقاط التتبع." action={<Link href="/zones" className="text-link" data-testid="link-add-zone-report">إضافة منطقة تغطية <ArrowLeft size={15} /></Link>} />}
          <div className="coverage-note"><ShieldCheck size={17} /><span>الفجوة المتوقعة تعني أن الرحلة تمر بمنطقة معروفة بضعف الإشارة.</span></div>
        </section>
        <section className="panel composition-panel"><div className="panel-heading"><div><p className="eyebrow">توزيع الانقطاعات</p><h2>قراءة سريعة</h2></div></div><div className="donut-wrap"><div className="donut" style={{ '--known': `${report.data.totalAffectedPoints ? (report.data.knownGapCount / report.data.totalAffectedPoints) * 100 : 0}%` } as CSSProperties}><div><b>{arabic.format(report.data.totalAffectedPoints)}</b><small>نقطة متأثرة</small></div></div></div><div className="donut-legend"><div><i className="known-dot" /><span>فجوات متوقعة</span><b>{arabic.format(report.data.knownGapCount)}</b></div><div><i className="abnormal-dot" /><span>انقطاعات مقلقة</span><b>{arabic.format(report.data.abnormalCount)}</b></div></div><p className="composition-copy">السياق هو الفرق بين فجوة عابرة وتنبيه يستحق التصعيد.</p></section>
      </div>
      <section className="panel incidents-panel"><div className="panel-heading"><div><p className="eyebrow">أثر الرحلات</p><h2>أحدث نقاط الانقطاع</h2></div><span className="table-note">{arabic.format(report.data.incidents.length)} سجل</span></div>
        {report.data.incidents.length ? <div className="table-scroll"><table className="data-table"><thead><tr><th>رقم التتبع</th><th>المسار</th><th>الإحداثيات</th><th>مدة الانقطاع</th><th>التوقيت</th><th /></tr></thead><tbody>{report.data.incidents.map((incident) => <tr key={incident.id} data-testid={`incident-${incident.id}`}><td><Link href={`/shipments/${incident.shipmentId}`} className="tracking-code mono" data-testid={`link-incident-${incident.id}`}>{incident.trackingCode}</Link></td><td>{incident.origin} <ArrowLeft size={12} /> {incident.destination}</td><td className="mono coordinate">{incident.lat.toFixed(3)}, {incident.lng.toFixed(3)}</td><td><span className="duration-badge">{arabic.format(incident.gapMinutes)} دقيقة</span></td><td>{compactDate(incident.timestamp)}</td><td><Link href={`/shipments/${incident.shipmentId}`} className="row-open" aria-label="عرض الشحنة" data-testid={`button-view-incident-${incident.id}`}><ChevronLeft size={17} /></Link></td></tr>)}</tbody></table></div> : <EmptyState title="لا توجد انقطاعات مسجلة" body="هذا جيد. ستظهر السجلات هنا عند وجود نقاط متأثرة." />}
      </section>
    </>}
  </div>;
}

const zoneSchema = z.object({
  name: z.string().trim().min(2, 'الاسم يجب أن يتكون من حرفين على الأقل'),
  latMin: z.coerce.number().min(-90).max(90),
  latMax: z.coerce.number().min(-90).max(90),
  lngMin: z.coerce.number().min(-180).max(180),
  lngMax: z.coerce.number().min(-180).max(180),
  expectedGapMinutes: z.coerce.number().min(1).max(1440),
}).refine((v) => v.latMin < v.latMax, { message: 'يجب أن يكون الحد الأدنى أقل من الأعلى', path: ['latMax'] })
  .refine((v) => v.lngMin < v.lngMax, { message: 'يجب أن يكون الحد الأدنى أقل من الأعلى', path: ['lngMax'] });
type ZoneForm = z.infer<typeof zoneSchema>;
function ZoneDialog({ onClose }: { onClose: () => void }) {
  const qc = useQueryClient();
  const mutation = useCreateZone();
  const form = useForm<ZoneForm>({ resolver: zodResolver(zoneSchema), defaultValues: { name: '', latMin: 21.2, latMax: 22.1, lngMin: 39, lngMax: 40, expectedGapMinutes: 18 } });
  const submit = form.handleSubmit((data) => mutation.mutate({ data }, { onSuccess: async () => { await Promise.all([qc.invalidateQueries({ queryKey: getListZonesQueryKey() }), qc.invalidateQueries({ queryKey: getGetDashboardSummaryQueryKey() }), qc.invalidateQueries({ queryKey: getGetCoverageReportQueryKey() })]); onClose(); } }));
  const fields: { name: keyof ZoneForm; label: string; step: string }[] = [{ name: 'latMin', label: 'خط العرض الأدنى', step: '0.0001' }, { name: 'latMax', label: 'خط العرض الأعلى', step: '0.0001' }, { name: 'lngMin', label: 'خط الطول الأدنى', step: '0.0001' }, { name: 'lngMax', label: 'خط الطول الأعلى', step: '0.0001' }];
  return <div className="modal-backdrop" role="presentation" onMouseDown={(e) => { if (e.target === e.currentTarget) onClose(); }}><section className="dialog zone-dialog" role="dialog" aria-modal="true" aria-labelledby="zone-title"><div className="dialog-head"><div><p className="eyebrow">تحديد نطاق جغرافي</p><h2 id="zone-title">إضافة منطقة تغطية</h2></div><button className="icon-button" onClick={onClose} aria-label="إغلاق" data-testid="button-close-zone"><X size={19} /></button></div><p className="dialog-intro">سجّل منطقة يتكرر فيها ضعف الإشارة، لتفسير الانقطاع المتوقع على المسارات.</p><Form {...form}><form onSubmit={submit} className="form-stack">
    <FormField control={form.control} name="name" render={({ field }) => <FormItem><FormLabel>اسم المنطقة</FormLabel><FormControl><input {...field} className="field" placeholder="مثال: طريق مكة — الطائف" data-testid="input-zone-name" /></FormControl><FormMessage /></FormItem>} />
    <div className="coordinates-title"><MapPin size={15} /> حدود الإحداثيات</div><div className="form-two">{fields.map(({ name, label, step }) => <FormField key={name} control={form.control} name={name} render={({ field }) => <FormItem><FormLabel>{label}</FormLabel><FormControl><input {...field} type="number" step={step} className="field ltr-field" data-testid={`input-zone-${name}`} /></FormControl><FormMessage /></FormItem>} />)}</div>
    <FormField control={form.control} name="expectedGapMinutes" render={({ field }) => <FormItem><FormLabel>مدة الانقطاع المتوقعة <small className="label-hint">بالدقائق</small></FormLabel><FormControl><input {...field} type="number" min="1" max="1440" className="field short-field" data-testid="input-zone-gap" /></FormControl><FormMessage /></FormItem>} />
    {mutation.isError && <p className="form-error" data-testid="error-create-zone">تعذرت إضافة المنطقة. حاول مرة أخرى.</p>}<div className="dialog-actions"><button type="button" className="button button-ghost" onClick={onClose} data-testid="button-cancel-zone">إلغاء</button><button type="submit" className="button button-primary" disabled={mutation.isPending} data-testid="button-submit-zone">{mutation.isPending ? 'جارٍ الحفظ...' : <><Plus size={16} /> حفظ المنطقة</>}</button></div>
  </form></Form></section></div>;
}
function ZonesPage() {
  const qc = useQueryClient();
  const query = useListZones();
  const remove = useDeleteZone();
  const [open, setOpen] = useState(false);
  const [confirm, setConfirm] = useState<CoverageZone | null>(null);
  const removeZone = () => { if (!confirm) return; remove.mutate({ id: confirm.id }, { onSuccess: async () => { await Promise.all([qc.invalidateQueries({ queryKey: getListZonesQueryKey() }), qc.invalidateQueries({ queryKey: getGetDashboardSummaryQueryKey() }), qc.invalidateQueries({ queryKey: getGetCoverageReportQueryKey() })]); setConfirm(null); } }); };
  return <div className="page-wrap"><PageTitle eyebrow="إشارة وسياق" title="مناطق التغطية" subtitle="علّم المناطق المعروفة بضعف الاتصال كي لا تُعامل كحالة طارئة." actions={<button className="button button-primary" onClick={() => setOpen(true)} data-testid="button-add-zone"><Plus size={17} /> إضافة منطقة</button>} />
    <div className="zone-explainer"><div className="explainer-icon"><Signal size={19} /></div><div><b>المعرفة تغيّر معنى الانقطاع</b><p>عندما تدخل شحنة منطقة مسجلة، نصنف فجوة الإشارة كمتوقعة طوال المدة التي حددتها.</p></div><Link href="/about" className="text-link" data-testid="link-zone-explainer">اعرف أكثر <ArrowLeft size={15} /></Link></div>
    {query.isLoading ? <div className="panel"><SkeletonRows rows={4} /></div> : query.isError ? <ErrorState retry={() => query.refetch()} /> : query.data?.length ? <div className="zone-cards">{query.data.map((zone) => <article className="zone-card" key={zone.id} data-testid={`card-zone-${zone.id}`}><div className="zone-card-top"><span className="zone-card-icon"><MapPin size={18} /></span><span className="expected-mark"><Check size={13} /> فجوة متوقعة</span></div><h2>{zone.name}</h2><div className="zone-coordinates"><div><span>خط العرض</span><code>{zone.latMin.toFixed(3)} — {zone.latMax.toFixed(3)}</code></div><div><span>خط الطول</span><code>{zone.lngMin.toFixed(3)} — {zone.lngMax.toFixed(3)}</code></div></div><div className="zone-card-bottom"><span><Clock3 size={15} /> مدة الانقطاع المتوقعة <b>{arabic.format(zone.expectedGapMinutes)} دقيقة</b></span><button className="delete-zone" onClick={() => setConfirm(zone)} data-testid={`button-delete-zone-${zone.id}`}><X size={15} /> إزالة المنطقة</button></div></article>)}</div> : <EmptyState title="لم تسجل مناطق تغطية بعد" body="أضف طريقاً أو نطاقاً تعرف أنه يسبب انقطاعاً متوقعاً." action={<button className="button button-primary" onClick={() => setOpen(true)} data-testid="button-empty-add-zone"><Plus size={16} /> إضافة منطقة تغطية</button>} />}
    {remove.isError && <p className="form-error inline-error" data-testid="error-delete-zone">تعذرت إزالة المنطقة. حاول مرة أخرى.</p>}
    {open && <ZoneDialog onClose={() => setOpen(false)} />}
    {confirm && <div className="modal-backdrop" role="presentation" onMouseDown={(e) => { if (e.target === e.currentTarget) setConfirm(null); }}><section className="dialog confirm-dialog" role="alertdialog" aria-modal="true"><span className="confirm-icon"><AlertTriangle size={21} /></span><h2>إزالة منطقة التغطية؟</h2><p>سيتم حذف <b>{confirm.name}</b>. قد تُصنّف الانقطاعات في هذا النطاق بشكل مختلف.</p><div className="dialog-actions"><button className="button button-ghost" onClick={() => setConfirm(null)} data-testid="button-cancel-delete-zone">إبقاء المنطقة</button><button className="button button-danger" onClick={removeZone} disabled={remove.isPending} data-testid="button-confirm-delete-zone">{remove.isPending ? 'جارٍ الحذف...' : 'إزالة المنطقة'}</button></div></section></div>}
  </div>;
}

function AboutPage() {
  return <div className="page-wrap about-wrap"><PageTitle eyebrow="لماذا GapTrack؟" title="الصمت لا يعني دائماً مشكلة." subtitle="في النقل، خسارة إشارة GPS متوقعة أحياناً. قرارك يحتاج إلى سياق، لا إلى تنبيه آخر." />
    <section className="about-hero"><div className="about-hero-copy"><span className="about-symbol"><Route size={23} /></span><p className="eyebrow">منظومة أوضح للرحلات</p><h2>من نقطة على الخريطة<br />إلى قرار <em>مفهوم.</em></h2><p>GapTrack أداة متابعة للشحنات تفسر انقطاع الإشارة في سياق الطرق والمناطق، لتعرف متى تنتظر ومتى تتصرف.</p><Link href="/shipments" className="button button-light" data-testid="button-about-shipments">ابدأ من الشحنات <ArrowLeft size={16} /></Link></div><div className="route-illustration" aria-hidden="true"><div className="illustration-ring ring-one" /><div className="illustration-ring ring-two" /><div className="route-node node-start"><span /><small>إشارة نشطة</small></div><div className="route-dash dash-one" /><div className="route-gap"><Signal size={18} /><small>منطقة معروفة</small></div><div className="route-dash dash-two" /><div className="route-node node-end"><span /><small>متابعة مستمرة</small></div><div className="illustration-coordinate">24°42′N · 46°40′E</div></div></section>
    <section className="about-principles"><div className="section-intro"><p className="eyebrow">مبدأ بسيط</p><h2>الإشارة لها سياق.</h2><p>نحوّل بيانات المسار إلى قراءة عملية تناسب فرق التشغيل.</p></div><div className="principle-list"><article><span className="principle-index">01</span><div><b>فجوة متوقعة</b><p>تقع الشحنة داخل منطقة تغطية مسجلة، والمدة ضمن النطاق المعروف. لا داعي للتصعيد الآن.</p></div><span className="principle-dot known" /></article><article><span className="principle-index">02</span><div><b>انقطاع مقلق</b><p>خرجت الرحلة عن سياق التغطية المعتاد. هذه إشارة واضحة تستحق مراجعة الفريق.</p></div><span className="principle-dot abnormal" /></article><article><span className="principle-index">03</span><div><b>تنبيه استباقي</b><p>تقترب الشحنة من منطقة ضعيفة. استعد لفجوة اتصال قبل حدوثها، لا بعدها.</p></div><span className="principle-dot predictive" /></article></div></section>
    <section className="comparison-section"><div className="section-intro"><p className="eyebrow">الفرق في التفاصيل</p><h2>تتبّع أساسي أم قراءة مفيدة؟</h2><p>الإحداثيات تخبرك أين كانت الشحنة. التفسير يساعدك أن تعرف ما يعنيه ذلك.</p></div><div className="comparison-table"><div className="comparison-head"><span>القدرة</span><span>تتبّع أساسي</span><span className="gaptrack-col">GapTrack</span></div><div><span>موقع الشحنة ونقاط المسار</span><span><Check size={16} /></span><span className="gaptrack-col"><Check size={16} /></span></div><div><span>تمييز المتوقع من المقلق</span><span className="no-check">—</span><span className="gaptrack-col"><Check size={16} /></span></div><div><span>مناطق التغطية الضعيفة</span><span className="no-check">—</span><span className="gaptrack-col"><Check size={16} /></span></div><div><span>تنبيه قبل دخول منطقة ضعيفة</span><span className="no-check">—</span><span className="gaptrack-col"><Check size={16} /></span></div><div><span>ملخص تأثير التغطية</span><span className="no-check">—</span><span className="gaptrack-col"><Check size={16} /></span></div></div></section>
    <section className="about-end"><span className="end-line" /><div><ShieldCheck size={24} /><h2>قرار أهدأ يبدأ بصورة أوضح.</h2><p>تابع المسار، افهم الإشارة، وخلي التصعيد للحالات التي تستحق.</p></div><Link href="/shipments" className="button button-primary" data-testid="button-about-cta">افتح لوحة الشحنات <ArrowLeft size={16} /></Link></section>
  </div>;
}

function NotFoundPage() {
  return <div className="not-found"><span className="not-found-number">٤٠٤</span><h1>هذه الصفحة خارج المسار.</h1><p>قد يكون الرابط قديماً أو غير صحيح.</p><Link href="/" className="button button-primary" data-testid="link-not-found-home"><ArrowUpLeft size={16} /> العودة إلى النظرة العامة</Link></div>;
}
function Router() {
  const [location] = useLocation();
  return <ErrorBoundary resetKey={location}><Shell><Switch>
    <WRoute path="/" component={HomePage} />
    <WRoute path="/shipments" component={ShipmentsPage} />
    <WRoute path="/shipments/:id" component={ShipmentDetailPage} />
    <WRoute path="/reports" component={ReportsPage} />
    <WRoute path="/zones" component={ZonesPage} />
    <WRoute path="/about" component={AboutPage} />
    <WRoute component={NotFoundPage} />
  </Switch></Shell></ErrorBoundary>;
}
function App() {
  return <QueryClientProvider client={queryClient}><TooltipProvider><Router /><Toaster /></TooltipProvider></QueryClientProvider>;
}
export default App;
