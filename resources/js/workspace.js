(() => {
  'use strict';
  const config = window.asteriaConfig;
  const root = document.getElementById('asteria-workspace');
  const money = new Intl.NumberFormat(config.locale, {style:'currency', currency:config.currency, maximumFractionDigits:2});
  const number = new Intl.NumberFormat(config.locale, {maximumFractionDigits:4});
  const percent = new Intl.NumberFormat(config.locale, {style:'percent', maximumFractionDigits:2});
  let state = null;

  const esc = value => String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
  const signedClass = value => Number(value) >= 0 ? 'asteria-positive' : 'asteria-negative';
  const idempotencyKey = () => typeof crypto.randomUUID === 'function' ? crypto.randomUUID() : Array.from(crypto.getRandomValues(new Uint32Array(4)), n => n.toString(16).padStart(8, '0')).join('-');
  const api = async (path, options = {}) => {
    const response = await fetch(config.restUrl + path, {
      credentials:'same-origin',
      ...options,
      headers:{'Content-Type':'application/json','X-WP-Nonce':config.nonce,...(options.headers || {})}
    });
    const payload = response.status === 204 ? null : await response.json();
    if (!response.ok) throw new Error(payload?.message || config.strings.error);
    return payload;
  };

  const tableRows = quotes => quotes.map(q => `<tr data-symbol="${esc(q.symbol)}"><td><button class="asteria-icon-button asteria-symbol" data-symbol="${esc(q.symbol)}">${esc(q.symbol)}</button></td><td>${number.format(q.last)}</td><td>${number.format(q.bid)}</td><td>${number.format(q.ask)}</td><td class="${signedClass(q.change)}">${q.change >= 0 ? '+' : ''}${number.format(q.change)} (${number.format(q.change_percent)}%)</td><td>${number.format(q.volume)}</td></tr>`).join('');
  const positionRows = positions => positions.length ? positions.map(p => `<tr><td>${esc(p.symbol)}</td><td>${number.format(p.quantity)}</td><td>${money.format(p.average_cost)}</td><td>${money.format(p.last)}</td><td>${money.format(p.market_value)}</td><td class="${signedClass(p.unrealized_pnl)}">${money.format(p.unrealized_pnl)}</td><td>${percent.format(p.weight)}</td></tr>`).join('') : '<tr><td colspan="7" class="asteria-muted">No positions yet. Use the paper order ticket to create one.</td></tr>';
  const orderRows = orders => orders.length ? orders.map(o => `<tr><td>${esc(o.created_at)}</td><td>${esc(o.symbol)}</td><td>${esc(o.side)}</td><td>${esc(o.order_type)}</td><td>${number.format(o.quantity)}</td><td>${o.fill_price ? money.format(o.fill_price) : '—'}</td><td>${esc(o.status)}</td><td>${o.status==='OPEN'?`<button class="asteria-icon-button asteria-cancel" data-order="${esc(o.id)}">Cancel</button>`:'—'}</td></tr>`).join('') : '<tr><td colspan="8" class="asteria-muted">No paper orders.</td></tr>';

  function render(data) {
    state = data;
    const p = data.portfolio, r = p.risk;
    root.innerHTML = `<div class="asteria-shell" data-theme="dark">
      <header class="asteria-topbar"><div class="asteria-brand"><span class="asteria-mark">A</span><h1>Asteria</h1></div><div class="asteria-search"><input id="asteria-command" type="search" aria-label="Search symbols" placeholder="Search local instruments (ASTR, US10Y, EURUSD…)"></div><span class="asteria-badge">${esc(config.strings.synthetic)}</span><button id="asteria-theme" class="asteria-icon-button" type="button" aria-label="Toggle color theme">◐</button></header>
      <div class="asteria-layout"><nav class="asteria-nav" aria-label="Workspace sections" role="tablist"><button role="tab" aria-selected="true" data-tab="overview">Overview</button><button role="tab" aria-selected="false" data-tab="markets">Markets</button><button role="tab" aria-selected="false" data-tab="portfolio">Portfolio</button><button role="tab" aria-selected="false" data-tab="trading">Paper trading</button><button role="tab" aria-selected="false" data-tab="news">News & macro</button></nav>
      <main id="asteria-main" class="asteria-main">
        <div class="asteria-notice"><strong>Standalone mode.</strong> All prices, history, news and economic events are deterministic synthetic data generated inside this WordPress plugin. Paper orders never leave this site.</div>
        <section class="asteria-panel" data-panel="overview"><div class="asteria-kpis">
          <div class="asteria-card"><span class="asteria-kpi-label">Portfolio equity</span><strong class="asteria-kpi-value">${money.format(p.equity)}</strong></div><div class="asteria-card"><span class="asteria-kpi-label">Cash</span><strong class="asteria-kpi-value">${money.format(p.cash)}</strong></div><div class="asteria-card"><span class="asteria-kpi-label">Unrealized P&L</span><strong class="asteria-kpi-value ${signedClass(p.unrealized_pnl)}">${money.format(p.unrealized_pnl)}</strong></div><div class="asteria-card"><span class="asteria-kpi-label">Annual volatility</span><strong class="asteria-kpi-value">${percent.format(r.annualized_volatility)}</strong></div><div class="asteria-card"><span class="asteria-kpi-label">95% daily VaR</span><strong class="asteria-kpi-value">${percent.format(r.historical_var_95)}</strong></div>
        </div><div class="asteria-grid"><article class="asteria-card"><h2 id="asteria-chart-title">${esc(data.market.quotes[0]?.symbol || 'ASTR')} · 90-day synthetic history</h2><canvas id="asteria-chart" class="asteria-chart" role="img" aria-label="Synthetic price history chart"></canvas></article><article class="asteria-card"><h2>Watchlist</h2><div class="asteria-table-wrap"><table class="asteria-table"><thead><tr><th>Symbol</th><th>Last</th><th>Bid</th><th>Ask</th><th>Change</th><th>Volume</th></tr></thead><tbody>${tableRows(data.market.quotes)}</tbody></table></div></article></div></section>
        <section class="asteria-panel" data-panel="markets" hidden><article class="asteria-card"><h2>Cross-asset market monitor</h2><form id="asteria-watchlist" class="asteria-form" style="grid-template-columns:minmax(180px,320px) auto;margin-bottom:12px"><label>Add local symbol<input name="symbol" placeholder="ASTR, US10Y, EURUSD, XAUUSD, BTCUSD" required></label><button type="submit">Add</button></form><div class="asteria-table-wrap"><table class="asteria-table"><thead><tr><th>Symbol</th><th>Last</th><th>Bid</th><th>Ask</th><th>Change</th><th>Volume</th><th>Action</th></tr></thead><tbody>${data.market.quotes.map(q => `<tr><td><button class="asteria-icon-button asteria-symbol" data-symbol="${esc(q.symbol)}">${esc(q.symbol)}</button></td><td>${number.format(q.last)}</td><td>${number.format(q.bid)}</td><td>${number.format(q.ask)}</td><td class="${signedClass(q.change)}">${q.change >= 0 ? '+' : ''}${number.format(q.change)} (${number.format(q.change_percent)}%)</td><td>${number.format(q.volume)}</td><td><button class="asteria-icon-button asteria-watch-remove" data-symbol="${esc(q.symbol)}">Remove</button></td></tr>`).join('')}</tbody></table></div></article></section>
        <section class="asteria-panel" data-panel="portfolio" hidden><div class="asteria-kpis"><div class="asteria-card"><span class="asteria-kpi-label">Total return</span><strong class="asteria-kpi-value ${signedClass(r.total_return)}">${percent.format(r.total_return)}</strong></div><div class="asteria-card"><span class="asteria-kpi-label">Sharpe</span><strong class="asteria-kpi-value">${number.format(r.sharpe_ratio)}</strong></div><div class="asteria-card"><span class="asteria-kpi-label">Max drawdown</span><strong class="asteria-kpi-value">${percent.format(r.max_drawdown)}</strong></div><div class="asteria-card"><span class="asteria-kpi-label">Expected shortfall</span><strong class="asteria-kpi-value">${percent.format(r.expected_shortfall_95)}</strong></div></div><article class="asteria-card"><h2>${esc(p.name)} positions</h2><div class="asteria-table-wrap"><table class="asteria-table"><thead><tr><th>Symbol</th><th>Quantity</th><th>Average cost</th><th>Last</th><th>Market value</th><th>Unrealized P&L</th><th>Weight</th></tr></thead><tbody>${positionRows(p.positions)}</tbody></table></div><p class="asteria-muted">${esc(r.methodology)}</p></article></section>
        <section class="asteria-panel" data-panel="trading" hidden><article class="asteria-card"><h2>Paper order ticket</h2><form id="asteria-order" class="asteria-form"><label>Symbol<input name="symbol" value="ASTR" required pattern="[A-Z0-9._/-]{1,32}"></label><label>Side<select name="side"><option>BUY</option><option>SELL</option></select></label><label>Type<select name="order_type"><option>MARKET</option><option>LIMIT</option></select></label><label>Quantity<input name="quantity" type="number" min="0.00000001" step="any" value="10" required></label><label>Limit price<input name="limit_price" type="number" min="0.00000001" step="any" placeholder="Only for LIMIT"></label><button type="submit">Submit paper order</button></form><p id="asteria-order-status" aria-live="polite"></p></article><article class="asteria-card" style="margin-top:12px"><h2>Order blotter</h2><div class="asteria-table-wrap"><table class="asteria-table"><thead><tr><th>Time</th><th>Symbol</th><th>Side</th><th>Type</th><th>Quantity</th><th>Fill</th><th>Status</th><th>Action</th></tr></thead><tbody>${orderRows(data.orders)}</tbody></table></div></article></section>
        <section class="asteria-panel" data-panel="news" hidden><div class="asteria-grid"><article class="asteria-card"><h2>Local synthetic news</h2><ul class="asteria-list">${data.news.map(n => `<li>${esc(n.headline)}<small>${esc(n.topic)} · ${esc(n.source)} · ${new Date(n.time).toLocaleTimeString(config.locale)}</small></li>`).join('')}</ul></article><article class="asteria-card"><h2>Economic calendar</h2><div class="asteria-table-wrap"><table class="asteria-table"><thead><tr><th>Event</th><th>Country</th><th>Prior</th><th>Consensus</th><th>Actual</th></tr></thead><tbody>${data.economics.map(e => `<tr><td>${esc(e.event)}</td><td>${esc(e.country)}</td><td>${number.format(e.prior)}${esc(e.unit)}</td><td>${number.format(e.consensus)}${esc(e.unit)}</td><td>${number.format(e.actual)}${esc(e.unit)}</td></tr>`).join('')}</tbody></table></div></article></div></section>
      </main></div></div>`;
    bind();
    drawChart(data.market.history);
  }

  function bind() {
    root.querySelectorAll('[data-tab]').forEach(button => button.addEventListener('click', () => {
      root.querySelectorAll('[data-tab]').forEach(item => item.setAttribute('aria-selected', String(item === button)));
      root.querySelectorAll('[data-panel]').forEach(panel => panel.hidden = panel.dataset.panel !== button.dataset.tab);
    }));
    root.querySelector('#asteria-theme').addEventListener('click', () => { const shell=root.querySelector('.asteria-shell'); shell.dataset.theme=shell.dataset.theme==='dark'?'light':'dark'; drawChart(state.market.history); });
    root.querySelectorAll('.asteria-symbol').forEach(button => button.addEventListener('click', async () => {
      try { const result=await api('/market-data/history/'+encodeURIComponent(button.dataset.symbol)+'?days=90'); state.market.history=result.data; root.querySelector('#asteria-chart-title').textContent=button.dataset.symbol+' · 90-day synthetic history'; drawChart(result.data); } catch(error) { showError(error.message); }
    }));
    root.querySelector('#asteria-command').addEventListener('keydown', event => { if(event.key==='Enter'){ event.preventDefault(); const match=state.market.quotes.find(q=>q.symbol===event.target.value.trim().toUpperCase()); if(match) root.querySelector(`.asteria-symbol[data-symbol="${match.symbol}"]`)?.click(); }});
    root.querySelector('#asteria-order').addEventListener('submit', submitOrder);
    root.querySelector('#asteria-watchlist').addEventListener('submit', async event => { event.preventDefault(); const symbol=String(new FormData(event.currentTarget).get('symbol')).trim().toUpperCase(); try { await api('/watchlist/'+encodeURIComponent(symbol),{method:'POST'}); const refreshed=await api('/workspace'); render(refreshed.data); root.querySelector('[data-tab="markets"]').click(); } catch(error) { showError(error.message); } });
    root.querySelectorAll('.asteria-watch-remove').forEach(button => button.addEventListener('click', async () => { try { await api('/watchlist/'+encodeURIComponent(button.dataset.symbol),{method:'DELETE'}); const refreshed=await api('/workspace'); render(refreshed.data); root.querySelector('[data-tab="markets"]').click(); } catch(error) { showError(error.message); } }));
    root.querySelectorAll('.asteria-cancel').forEach(button => button.addEventListener('click', async () => { try { await api('/paper-orders/'+encodeURIComponent(button.dataset.order),{method:'DELETE'}); const refreshed=await api('/workspace'); render(refreshed.data); root.querySelector('[data-tab="trading"]').click(); } catch(error) { showError(error.message); } }));
    window.addEventListener('resize', () => drawChart(state.market.history), {passive:true});
  }

  async function submitOrder(event) {
    event.preventDefault();
    const form=new FormData(event.currentTarget), status=root.querySelector('#asteria-order-status');
    const body={portfolio_id:state.portfolio.id,symbol:String(form.get('symbol')).toUpperCase(),side:form.get('side'),order_type:form.get('order_type'),quantity:Number(form.get('quantity'))};
    if(form.get('limit_price')) body.limit_price=Number(form.get('limit_price'));
    status.textContent='Submitting locally…';
    try { const result=await api('/paper-orders',{method:'POST',headers:{'Idempotency-Key':idempotencyKey()},body:JSON.stringify(body)}); status.textContent=`${result.data.status}: ${result.data.side} ${result.data.quantity} ${result.data.symbol}${result.data.fill_price?' at '+money.format(result.data.fill_price):''}`; const refreshed=await api('/workspace'); render(refreshed.data); root.querySelector('[data-tab="trading"]').click(); } catch(error) { status.textContent=error.message; status.className='asteria-negative'; }
  }

  function drawChart(bars) {
    const canvas=root.querySelector('#asteria-chart'); if(!canvas||!bars?.length) return;
    const ratio=window.devicePixelRatio||1, rect=canvas.getBoundingClientRect(); canvas.width=Math.max(300,rect.width)*ratio; canvas.height=280*ratio;
    const ctx=canvas.getContext('2d'); ctx.scale(ratio,ratio); const width=canvas.width/ratio,height=canvas.height/ratio,pad=24;
    const values=bars.map(b=>Number(b.close)), min=Math.min(...values),max=Math.max(...values),span=max-min||1;
    const style=getComputedStyle(root.querySelector('.asteria-wrap')); ctx.clearRect(0,0,width,height); ctx.strokeStyle=style.getPropertyValue('--a-line'); ctx.lineWidth=1;
    for(let i=0;i<5;i++){const y=pad+(height-pad*2)*i/4;ctx.beginPath();ctx.moveTo(pad,y);ctx.lineTo(width-pad,y);ctx.stroke();}
    ctx.strokeStyle=style.getPropertyValue('--a-accent');ctx.lineWidth=2;ctx.beginPath();values.forEach((v,i)=>{const x=pad+(width-pad*2)*i/(values.length-1),y=height-pad-(v-min)/span*(height-pad*2);i?ctx.lineTo(x,y):ctx.moveTo(x,y);});ctx.stroke();
    ctx.fillStyle=style.getPropertyValue('--a-muted');ctx.font='11px sans-serif';ctx.fillText(number.format(max),pad,14);ctx.fillText(number.format(min),pad,height-6);
  }
  function showError(message){root.insertAdjacentHTML('afterbegin',`<div class="asteria-notice asteria-error">${esc(message)}</div>`);}
  api('/workspace').then(payload => render(payload.data)).catch(error => { root.innerHTML=`<div class="asteria-loading asteria-error"><div><h1>Asteria</h1><p>${esc(error.message)}</p></div></div>`; });
})();
