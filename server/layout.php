<?php
function pageStart(string $title, string $heading, string $section = ''): void
{
    global $isManager;
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>StockSmart - <?= e($title) ?></title>
    <link rel="stylesheet" href="style.css">
  <script defer>
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('select').forEach((sel) => {
        const wrap = document.createElement('div');
        wrap.className = 'custom-select-wrap';
        sel.parentNode.insertBefore(wrap, sel);
        wrap.appendChild(sel);

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'custom-select-trigger';
        if (sel.disabled) trigger.disabled = true;
        wrap.appendChild(trigger);

                const menu = document.createElement('div');
        menu.className = 'custom-select-menu';
        wrap.appendChild(menu);

        let searchInput = null;
        if (sel.options.length > 8) {
          searchInput = document.createElement('input');
          searchInput.type = 'text';
          searchInput.className = 'custom-select-search';
          searchInput.placeholder = 'Type to filter...';
          searchInput.addEventListener('click', (e) => e.stopPropagation());
          searchInput.addEventListener('input', () => {
            const q = searchInput.value.toLowerCase();
            menu.querySelectorAll('.custom-select-option').forEach((el) => {
              el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
                    // Upgrade all browser confirm() forms to an in-page modal
              document.querySelectorAll('form[onsubmit*="confirm("]').forEach((form) => {
                const raw = form.getAttribute('onsubmit') || '';
                const match = raw.match(/confirm\(['"](.+?)['"]\)/);
                const msg = match ? match[1] : 'Are you sure you want to delete this record?';
                form.removeAttribute('onsubmit');

                form.addEventListener('submit', (e) => {
                  if (form.dataset.confirmed === '1') return;
                  e.preventDefault();

                  const backdrop = document.createElement('div');
                  backdrop.className = 'ss-modal-backdrop';
                  backdrop.innerHTML = `
                    <div class="ss-modal" role="dialog" aria-modal="true">
                      <h4>Confirm Deletion</h4>
                      <p>${msg}</p>
                      <div class="ss-modal-actions">
                        <button type="button" class="btn-secondary" id="ssCancel">Cancel</button>
                        <button type="button" class="ss-btn-danger" id="ssConfirm">Delete</button>
                      </div>
                    </div>
                  `;
                  document.body.appendChild(backdrop);

                  backdrop.querySelector('#ssCancel').addEventListener('click', () => backdrop.remove());
                  backdrop.addEventListener('click', (ev) => { if (ev.target === backdrop) backdrop.remove(); });
                  backdrop.querySelector('#ssConfirm').addEventListener('click', () => {
                    form.dataset.confirmed = '1';
                    backdrop.remove();
                    form.submit();
                  });
                });
              });
            });
          });
          menu.appendChild(searchInput);
        }

        const sync = () => {
          const opt = sel.selectedOptions[0];
          trigger.textContent = opt ? opt.textContent : 'Select...';
          menu.querySelectorAll('.custom-select-option').forEach((el, i) => {
            el.classList.toggle('selected', i === sel.selectedIndex);
          });
        };

        Array.from(sel.options).forEach((opt, idx) => {
          const item = document.createElement('div');
          item.className = 'custom-select-option';
          item.textContent = opt.textContent;
          item.addEventListener('click', () => {
            sel.selectedIndex = idx;
            sel.dispatchEvent(new Event('change', { bubbles: true }));
            sync();
            wrap.classList.remove('open');
          });
          menu.appendChild(item);
        });

        trigger.addEventListener('click', (e) => {
          e.stopPropagation();
          document.querySelectorAll('.custom-select-wrap.open').forEach((w) => {
            if (w !== wrap) w.classList.remove('open');
          });
          wrap.classList.toggle('open');
          if (wrap.classList.contains('open') && searchInput) {
            searchInput.value = '';
            menu.querySelectorAll('.custom-select-option').forEach((el) => (el.style.display = ''));
            setTimeout(() => searchInput.focus(), 0);
          }
        });

        sel.form?.addEventListener('reset', () => setTimeout(sync, 0));
        sync();
      });

      document.addEventListener('click', () => {
        document.querySelectorAll('.custom-select-wrap.open').forEach((w) => w.classList.remove('open'));
      });
    });
  </script>
</head>

<body>
<header>
  <div class="brand">
    <h1>StockSmart</h1>
    <h2><?= e($heading) ?></h2>
    <h3>Never run out. Never run over.</h3>
  </div>
  <nav>
    <?php if ($section !== ''): ?>
      <a href="index.php"<?= $section === 'dashboard' ? ' class="active"' : '' ?>>Dashboard</a>
      <?php if ($isManager): ?>
        <a href="products.php"<?= $section === 'products' ? ' class="active"' : '' ?>>Products</a>
      <?php endif; ?>
      <a href="inventory.php"<?= $section === 'inventory' ? ' class="active"' : '' ?>>Inventory</a>
      <a href="sales.php"<?= $section === 'sales' ? ' class="active"' : '' ?>>Sales</a>
      <?php if ($isManager): ?>
        <a href="reports.php"<?= $section === 'reports' ? ' class="active"' : '' ?>>Reports</a>
      <?php endif; ?>
    <?php endif; ?>
  </nav>
</header>
<?php
}

function pageMessage(string $error, string $message = ''): void
{
    if ($error !== '') {
        echo '<p style="color: #b00020; font-weight: bold;" role="alert">' . e($error) . '</p>';
    }
    if ($message !== '') {
        echo '<p class="note" role="status">' . e($message) . '</p>';
    }
}
