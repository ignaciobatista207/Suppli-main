/*
 * Caja registradora: carrito, buscador en vivo y totales.
 * Los totales que se muestran aquí son solo una guía: el servidor recalcula TODO
 * desde los precios de la base y nunca confía en lo que envía el navegador.
 * Todo el dinero se maneja en centavos enteros.
 */
(function () {
  'use strict';

  var root = document.getElementById('caja-app');
  var form = document.getElementById('sale-form');
  if (!root || !form) return;

  var MAX_QTY = 9999;
  var MAX_LINES = 100;
  var MAX_TOTAL = 999999999999; // 9.999.999.999,99 (columna DECIMAL(12,2))
  var searchUrl = root.getAttribute('data-search-url');

  var el = {
    search: document.getElementById('caja-search'),
    results: document.getElementById('caja-results'),
    status: document.getElementById('caja-status'),
    list: document.getElementById('cart-list'),
    empty: document.getElementById('cart-empty'),
    template: document.getElementById('cart-item-template'),
    fields: document.getElementById('cart-fields'),
    discountType: document.getElementById('discount-type'),
    discountWrap: document.getElementById('discount-wrap'),
    discountValue: document.getElementById('discount-value'),
    discountLabel: document.getElementById('discount-label'),
    discountHint: document.getElementById('discount-hint'),
    cashBlock: document.getElementById('cash-block'),
    received: document.getElementById('amount-received'),
    change: document.getElementById('change-line'),
    clear: document.getElementById('clear-cart')
  };
  var methodRadios = Array.prototype.slice.call(form.querySelectorAll('input[name="payment_method"]'));

  var cart = [];
  var submitting = false;

  // ---------------------------------------------------------------- utilidades

  function all(selector) {
    return Array.prototype.slice.call(document.querySelectorAll(selector));
  }

  function setAll(selector, text) {
    all(selector).forEach(function (node) { node.textContent = text; });
  }

  function say(message) {
    el.status.textContent = message;
  }

  function formatCents(cents) {
    var negative = cents < 0;
    var abs = Math.abs(cents);
    var whole = Math.floor(abs / 100);
    var rest = abs % 100;
    var text = String(whole).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return (negative ? '-' : '') + '$ ' + text + ',' + (rest < 10 ? '0' : '') + rest;
  }

  function parseCents(text, allowZero) {
    var m = /^(\d{1,8})(?:[.,](\d{1,2}))?$/.exec(String(text).trim());
    if (!m) return null;
    var fraction = m[2] ? parseInt((m[2] + '0').slice(0, 2), 10) : 0;
    var cents = parseInt(m[1], 10) * 100 + fraction;
    if (cents === 0 && !allowZero) return null;
    return cents;
  }

  // Devuelve centésimas de punto porcentual (0 a 10000) o null
  function parsePercent(text) {
    var m = /^(\d{1,3})(?:[.,](\d{1,2}))?$/.exec(String(text).trim());
    if (!m) return null;
    var hundredths = parseInt(m[1], 10) * 100 + (m[2] ? parseInt((m[2] + '0').slice(0, 2), 10) : 0);
    return hundredths <= 10000 ? hundredths : null;
  }

  // (centavos × centésimas + 5000) / 10000: redondeo half-up, igual que el servidor
  function percentOf(cents, hundredths) {
    if (typeof BigInt === 'function') {
      return Number((BigInt(cents) * BigInt(hundredths) + BigInt(5000)) / BigInt(10000));
    }
    return Math.floor((cents * hundredths + 5000) / 10000);
  }

  function currentMethod() {
    for (var i = 0; i < methodRadios.length; i++) {
      if (methodRadios[i].checked) return methodRadios[i].value;
    }
    return '';
  }

  function maxQtyFor(line) {
    return Math.min(line.stock, MAX_QTY);
  }

  function lineIsValid(line) {
    return line.qty >= 1 && line.qty <= maxQtyFor(line);
  }

  // ------------------------------------------------------------------- cálculo

  function compute() {
    var problems = [];
    var subtotal = 0;
    var linesOk = true;

    cart.forEach(function (line) {
      subtotal += line.price * Math.max(line.qty, 0);
      if (!lineIsValid(line)) linesOk = false;
    });

    if (!cart.length) problems.push('Agrega al menos un producto.');
    else if (!linesOk) problems.push('Revisa las cantidades: hay líneas sin stock suficiente.');
    if (subtotal > MAX_TOTAL) problems.push('El subtotal supera el máximo permitido.');

    var discount = 0;
    var type = el.discountType.value;

    if (type === 'percent') {
      var hundredths = parsePercent(el.discountValue.value);
      if (hundredths === null) problems.push('Revisa el porcentaje de descuento (0 a 100, hasta 2 decimales).');
      else discount = percentOf(subtotal, hundredths);
    } else if (type === 'amount') {
      var amount = parseCents(el.discountValue.value, true);
      if (amount === null) problems.push('Revisa el monto del descuento (coma o punto, sin separador de miles).');
      else if (amount > subtotal) problems.push('El descuento no puede superar el subtotal.');
      else discount = amount;
    }

    var total = subtotal - discount;
    var method = currentMethod();
    var received = null;

    if (!method) {
      problems.push('Elige un método de pago.');
    } else if (method === 'efectivo') {
      received = parseCents(el.received.value, true);
      if (received === null) problems.push('Ingresa el monto recibido.');
      else if (received < total) problems.push('El monto recibido no alcanza para cubrir el total.');
    }

    return {
      subtotal: subtotal,
      discount: discount,
      total: total,
      method: method,
      received: received,
      problems: problems
    };
  }

  function update() {
    var s = compute();

    setAll('[data-subtotal]', formatCents(s.subtotal));
    setAll('[data-discount]', '− ' + formatCents(s.discount));
    setAll('[data-total]', formatCents(s.total));

    var cash = s.method === 'efectivo';
    el.cashBlock.hidden = !cash;
    el.received.disabled = !cash;

    if (cash) {
      if (s.received === null) el.change.textContent = 'Vuelto: ingresa el monto recibido';
      else if (s.received < s.total) el.change.textContent = 'Faltan ' + formatCents(s.total - s.received);
      else el.change.textContent = 'Vuelto: ' + formatCents(s.received - s.total);
    }

    var ok = s.problems.length === 0 && !submitting;
    all('[data-confirm]').forEach(function (button) { button.disabled = !ok; });
    setAll('[data-hint]', s.problems.length ? s.problems[0] : 'Todo listo para confirmar.');

    return s;
  }

  // --------------------------------------------------------------- carrito (UI)

  function refreshLine(line, writeInput) {
    var n = line.nodes;
    if (!n) return;

    if (writeInput) n.qty.value = String(line.qty);
    n.qty.max = String(Math.max(1, maxQtyFor(line)));
    n.subtotal.textContent = formatCents(line.price * Math.max(line.qty, 0));

    var warn = '';
    if (line.stock < 1) warn = 'Sin stock disponible.';
    else if (line.qty > MAX_QTY) warn = 'Máximo 9.999 unidades por producto.';
    else if (line.qty > line.stock) warn = 'Solo hay ' + line.stock + ' en stock.';
    else if (line.qty < 1) warn = 'Indica una cantidad de 1 o más.';

    n.warn.hidden = warn === '';
    n.warn.textContent = warn;
  }

  function setQty(line, qty, writeInput) {
    line.qty = qty;
    refreshLine(line, writeInput);
    update();
  }

  function renderCart() {
    el.list.textContent = '';

    cart.forEach(function (line) {
      var node = el.template.content.firstElementChild.cloneNode(true);
      var nodes = {
        qty: node.querySelector('[data-qty]'),
        subtotal: node.querySelector('[data-subtotal]'),
        warn: node.querySelector('[data-warn]')
      };
      var minus = node.querySelector('[data-minus]');
      var plus = node.querySelector('[data-plus]');
      var remove = node.querySelector('[data-remove]');

      node.querySelector('[data-name]').textContent = line.name;
      node.querySelector('[data-code]').textContent = '#' + line.code;
      node.querySelector('[data-unit]').textContent = formatCents(line.price);
      minus.setAttribute('aria-label', 'Restar 1 a ' + line.name);
      plus.setAttribute('aria-label', 'Sumar 1 a ' + line.name);
      remove.setAttribute('aria-label', 'Quitar ' + line.name + ' de la venta');
      nodes.qty.setAttribute('aria-label', 'Cantidad de ' + line.name);

      minus.addEventListener('click', function () {
        if (line.qty > 1) setQty(line, line.qty - 1, true);
      });

      plus.addEventListener('click', function () {
        if (line.qty >= maxQtyFor(line)) {
          say('No hay más stock de ' + line.name + '.');
          return;
        }
        setQty(line, line.qty + 1, true);
      });

      nodes.qty.addEventListener('input', function () {
        var value = parseInt(nodes.qty.value, 10);
        setQty(line, isNaN(value) ? 0 : value, false);
      });

      nodes.qty.addEventListener('change', function () {
        if (line.qty < 1) setQty(line, 1, true);
      });

      remove.addEventListener('click', function () {
        cart.splice(cart.indexOf(line), 1);
        renderCart();
        update();
        say(line.name + ' quitado de la venta.');
        el.search.focus();
      });

      line.nodes = nodes;
      el.list.appendChild(node);
      refreshLine(line, true);
    });

    el.empty.hidden = cart.length > 0;
  }

  function addProduct(product) {
    var existing = null;
    cart.forEach(function (line) { if (line.id === product.id) existing = line; });

    if (existing) {
      existing.stock = product.stock;
      existing.price = product.price_cents;
      if (existing.qty >= maxQtyFor(existing)) {
        say('No hay más stock de ' + product.name + '.');
      } else {
        setQty(existing, existing.qty + 1, true);
        say(product.name + ': ' + existing.qty + ' en la venta.');
      }
    } else {
      if (cart.length >= MAX_LINES) {
        say('Una venta puede tener como máximo ' + MAX_LINES + ' productos distintos.');
        return;
      }
      cart.push({
        id: product.id,
        code: product.code,
        name: product.name,
        price: product.price_cents,
        stock: product.stock,
        qty: 1,
        nodes: null
      });
      renderCart();
      update();
      say(product.name + ' agregado.');
    }

    el.search.value = '';
    closeResults();
    el.search.focus();
  }

  // ------------------------------------------------------------------ buscador

  var debounceTimer = null;
  var controller = null;
  var results = [];
  var activeIndex = -1;
  var lastQuery = '';
  var pendingEnter = false;

  function minLengthFor(q) {
    return /^\d+$/.test(q) ? 1 : 2;
  }

  function closeResults() {
    el.results.hidden = true;
    el.search.setAttribute('aria-expanded', 'false');
    el.search.removeAttribute('aria-activedescendant');
    activeIndex = -1;
  }

  function setActive(index) {
    var options = el.results.querySelectorAll('[role="option"]');
    for (var i = 0; i < options.length; i++) {
      var on = i === index;
      options[i].setAttribute('aria-selected', on ? 'true' : 'false');
      options[i].classList.toggle('bg-accent', on);
      options[i].classList.toggle('text-onaccent', on);
      if (on) {
        el.search.setAttribute('aria-activedescendant', options[i].id);
        options[i].scrollIntoView({ block: 'nearest' });
      }
    }
    activeIndex = index;
  }

  function choose(index) {
    var product = results[index];
    if (!product) return;
    if (product.stock < 1) {
      say(product.name + ': sin stock.');
      return;
    }
    addProduct(product);
  }

  function chooseFirstEnabled() {
    for (var i = 0; i < results.length; i++) {
      if (results[i].stock > 0) {
        choose(i);
        return;
      }
    }
    say('Los productos encontrados no tienen stock.');
  }

  function showMessage(text) {
    el.results.textContent = '';
    var item = document.createElement('li');
    item.setAttribute('role', 'presentation');
    item.className = 'px-4 py-3 text-sm';
    item.textContent = text;
    el.results.appendChild(item);
    el.results.hidden = false;
    el.search.setAttribute('aria-expanded', 'true');
  }

  function renderResults(items, q) {
    results = items;
    lastQuery = q;
    activeIndex = -1;
    el.search.removeAttribute('aria-activedescendant');
    el.results.textContent = '';

    if (!items.length) {
      showMessage('Sin resultados para «' + q + '».');
      pendingEnter = false;
      return;
    }

    items.forEach(function (product, index) {
      var out = product.stock < 1;
      var li = document.createElement('li');
      li.id = 'caja-opt-' + index;
      li.setAttribute('role', 'option');
      li.setAttribute('aria-selected', 'false');
      if (out) li.setAttribute('aria-disabled', 'true');
      li.className = 'flex cursor-pointer flex-wrap items-center justify-between gap-x-4 gap-y-1 px-4 py-3 ' +
        (out ? 'opacity-70' : 'hover:bg-ink/10');

      var main = document.createElement('span');
      main.className = 'min-w-0 break-words font-semibold';
      main.textContent = '#' + product.code + ' ' + product.name;

      var meta = document.createElement('span');
      meta.className = 'text-sm tabular-nums';
      meta.textContent = product.price_text + ' · ' + (out ? 'Sin stock' : 'Stock: ' + product.stock) +
        ' · ' + product.gondola;

      li.appendChild(main);
      li.appendChild(meta);
      li.addEventListener('mousedown', function (e) { e.preventDefault(); });
      li.addEventListener('click', function () { choose(index); });
      el.results.appendChild(li);
    });

    el.results.hidden = false;
    el.search.setAttribute('aria-expanded', 'true');

    if (pendingEnter) {
      pendingEnter = false;
      chooseFirstEnabled();
    }
  }

  function runSearch(q) {
    if (controller) controller.abort();
    controller = new AbortController();
    var mine = controller;

    fetch(searchUrl + '?q=' + encodeURIComponent(q), {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin',
      signal: mine.signal
    })
      .then(function (response) {
        if (!response.ok) throw new Error('http');
        return response.json();
      })
      .then(function (data) {
        if (mine !== controller) return;
        renderResults(data && data.results ? data.results : [], q);
      })
      .catch(function (err) {
        if (err && err.name === 'AbortError') return;
        pendingEnter = false;
        showMessage('No se pudo buscar. Revisa tu conexión o vuelve a iniciar sesión.');
      });
  }

  el.search.addEventListener('input', function () {
    clearTimeout(debounceTimer);
    pendingEnter = false;
    var q = el.search.value.trim();

    if (q.length < minLengthFor(q)) {
      if (controller) controller.abort();
      closeResults();
      return;
    }

    debounceTimer = setTimeout(function () { runSearch(q); }, 250);
  });

  el.search.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      if (el.results.hidden || !results.length) return;
      e.preventDefault();
      var next = e.key === 'ArrowDown'
        ? (activeIndex + 1) % results.length
        : (activeIndex - 1 + results.length) % results.length;
      setActive(next);
    } else if (e.key === 'Enter') {
      e.preventDefault();
      var q = el.search.value.trim();
      if (!q) return;
      if (q.length < minLengthFor(q)) {
        say('Escribe al menos ' + minLengthFor(q) + ' caracteres para buscar.');
        return;
      }
      if (!el.results.hidden && lastQuery === q && results.length) {
        if (activeIndex >= 0) choose(activeIndex);
        else chooseFirstEnabled();
        return;
      }
      clearTimeout(debounceTimer);
      pendingEnter = true;
      runSearch(q);
    } else if (e.key === 'Escape') {
      if (!el.results.hidden) {
        e.preventDefault();
        closeResults();
      }
    }
  });

  document.addEventListener('click', function (e) {
    if (!el.search.parentNode.contains(e.target)) closeResults();
  });

  // --------------------------------------------------------- descuento y pago

  function syncDiscountUi() {
    var type = el.discountType.value;
    el.discountWrap.hidden = type === 'none';
    el.discountValue.disabled = type === 'none';

    if (type === 'percent') {
      el.discountLabel.textContent = 'Porcentaje de descuento';
      el.discountValue.placeholder = '10';
      el.discountHint.textContent = 'Entre 0 y 100, hasta 2 decimales.';
    } else if (type === 'amount') {
      el.discountLabel.textContent = 'Monto del descuento';
      el.discountValue.placeholder = '0,00';
      el.discountHint.textContent = 'No puede superar el subtotal.';
    } else {
      el.discountHint.textContent = '';
    }
  }

  el.discountType.addEventListener('change', function () { syncDiscountUi(); update(); });
  el.discountValue.addEventListener('input', update);
  el.received.addEventListener('input', update);
  methodRadios.forEach(function (radio) { radio.addEventListener('change', update); });

  function clearCart() {
    if (!cart.length) return;
    if (!window.confirm('¿Vaciar la venta? Se quitarán todos los productos.')) return;
    cart = [];
    renderCart();
    update();
    say('Venta vaciada.');
    el.search.focus();
  }

  if (el.clear) el.clear.addEventListener('click', clearCart);
  all('[data-clear-mobile]').forEach(function (button) { button.addEventListener('click', clearCart); });

  // Panel "Venta registrada": el botón "Nueva venta" lo oculta y vuelve al buscador
  all('[data-new-sale]').forEach(function (button) {
    button.addEventListener('click', function () {
      var panel = document.getElementById('sale-receipt');
      if (panel) panel.hidden = true;
      el.search.focus();
    });
  });

  // ------------------------------------------------------------------- envío

  function syncFields() {
    el.fields.textContent = '';
    cart.forEach(function (line, index) {
      [['product_id', line.id], ['quantity', line.qty]].forEach(function (pair) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'items[' + index + '][' + pair[0] + ']';
        input.value = String(pair[1]);
        el.fields.appendChild(input);
      });
    });
  }

  // Enter dentro de un campo NO confirma la venta (evita cobrar sin querer)
  form.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target && e.target.tagName === 'INPUT') e.preventDefault();
  });

  form.addEventListener('submit', function (e) {
    var state = compute();

    if (submitting || state.problems.length) {
      e.preventDefault();
      if (state.problems.length) say(state.problems[0]);
      return;
    }

    // Doble envío: se bloquea el botón y, además, el servidor consume un token de un solo uso
    submitting = true;
    syncFields();
    all('[data-confirm]').forEach(function (button) {
      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
    });
    setAll('[data-confirm-label]', 'Procesando…');
  });

  // Al volver con el botón "Atrás" la página puede quedar con el botón bloqueado
  window.addEventListener('pageshow', function (e) {
    if (!e.persisted) return;
    submitting = false;
    all('[data-confirm]').forEach(function (button) { button.removeAttribute('aria-busy'); });
    setAll('[data-confirm-label]', 'Confirmar venta');
    update();
  });

  // -------------------------------------------------------------------- inicio

  var init = document.getElementById('cart-init');

  if (init) {
    try {
      JSON.parse(init.textContent || '[]').forEach(function (p) {
        cart.push({
          id: p.id,
          code: p.code,
          name: String(p.name),
          price: p.price_cents,
          stock: p.stock,
          qty: p.quantity,
          nodes: null
        });
      });
    } catch (err) {
      cart = [];
    }
  }

  syncDiscountUi();
  renderCart();
  update();
  el.search.focus();
})();