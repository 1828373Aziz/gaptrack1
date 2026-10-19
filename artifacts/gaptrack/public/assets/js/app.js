(() => {
  const numberFormat = new Intl.NumberFormat('ar-SA');

  const menuToggle = document.querySelector('[data-menu-toggle]');
  const sidebar = document.querySelector('.sidebar');
  menuToggle?.addEventListener('click', () => sidebar?.classList.toggle('is-open'));
  document.addEventListener('click', (event) => {
    if (!sidebar?.classList.contains('is-open')) return;
    if (sidebar.contains(event.target) || menuToggle?.contains(event.target)) return;
    sidebar.classList.remove('is-open');
  });

  const searchInput = document.querySelector('[data-shipment-search]');
  const filterSelect = document.querySelector('[data-shipment-filter]');
  const shipmentRows = [...document.querySelectorAll('[data-shipment-row]')];
  const emptyFilter = document.querySelector('[data-filter-empty]');
  const applyShipmentFilters = () => {
    if (!shipmentRows.length) return;
    const search = (searchInput?.value || '').trim().toLocaleLowerCase('ar');
    const filter = filterSelect?.value || 'all';
    let visible = 0;
    shipmentRows.forEach((row) => {
      const matchesText = (row.dataset.search || '').toLocaleLowerCase('ar').includes(search);
      const matchesState = filter === 'all' || row.dataset.health === filter;
      const show = matchesText && matchesState;
      row.hidden = !show;
      if (show) visible += 1;
    });
    if (emptyFilter) emptyFilter.hidden = visible !== 0;
  };
  searchInput?.addEventListener('input', applyShipmentFilters);
  filterSelect?.addEventListener('change', applyShipmentFilters);

  document.querySelectorAll('[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm || 'تأكيد العملية؟')) event.preventDefault();
    });
  });

  const routeDataElement = document.querySelector('#route-data');
  const routeMap = document.querySelector('[data-route-map]');
  if (!routeDataElement || !routeMap) return;

  let points = [];
  try {
    points = JSON.parse(routeDataElement.textContent || '[]');
  } catch {
    points = [];
  }
  const slider = document.querySelector('[data-route-slider]');
  const playButton = document.querySelector('[data-play-route]');
  const countLabel = document.querySelector('[data-route-count]');
  const currentLocation = document.querySelector('[data-route-current]');
  const markers = [...routeMap.querySelectorAll('[data-route-marker]')];
  const focusButtons = [...document.querySelectorAll('[data-focus-point]')];
  let activeIndex = 0;
  let intervalId = null;

  const updateRoute = (nextIndex) => {
    if (!points.length) return;
    activeIndex = Math.max(0, Math.min(points.length - 1, Number(nextIndex) || 0));
    if (slider) slider.value = String(activeIndex);
    markers.forEach((marker, index) => {
      marker.classList.toggle('is-active', index === activeIndex);
      const halo = marker.querySelector('.marker-halo');
      if (halo) halo.setAttribute('r', index === activeIndex ? '14' : '10');
    });
    if (countLabel) countLabel.textContent = `${numberFormat.format(activeIndex + 1)} / ${numberFormat.format(points.length)}`;
    const point = points[activeIndex];
    if (currentLocation && point) {
      currentLocation.replaceChildren();
      const dot = document.createElement('span');
      dot.className = `event-dot ${point.classification === 'abnormal_gap' ? 'danger' : point.classification === 'known_gap' ? 'warning' : 'success'}`;
      const note = document.createElement('span');
      note.textContent = point.note || 'نقطة مسجلة على المسار';
      const coordinates = document.createElement('code');
      coordinates.textContent = `${Number(point.lat).toFixed(4)}، ${Number(point.lng).toFixed(4)}`;
      const time = document.createElement('time');
      time.textContent = point.time ? new Date(point.time).toLocaleString('ar-SA', { day: 'numeric', month: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';
      currentLocation.append(dot, note, coordinates, time);
    }
  };

  const stopPlayback = () => {
    if (intervalId !== null) window.clearInterval(intervalId);
    intervalId = null;
    if (playButton) playButton.innerHTML = 'تشغيل المسار <span>▶</span>';
  };

  slider?.addEventListener('input', () => {
    stopPlayback();
    updateRoute(slider.value);
  });
  playButton?.addEventListener('click', () => {
    if (intervalId !== null) {
      stopPlayback();
      return;
    }
    if (points.length < 2) return;
    if (activeIndex >= points.length - 1) updateRoute(0);
    if (playButton) playButton.innerHTML = 'إيقاف المسار <span>Ⅱ</span>';
    intervalId = window.setInterval(() => {
      if (activeIndex >= points.length - 1) {
        stopPlayback();
        return;
      }
      updateRoute(activeIndex + 1);
    }, 1100);
  });
  focusButtons.forEach((button) => {
    button.addEventListener('click', () => {
      stopPlayback();
      updateRoute(button.dataset.focusPoint);
      routeMap.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  });
})();
