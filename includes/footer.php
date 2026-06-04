 <div class="text-center mt-4 text-muted small">
    &copy; <?= date('Y') ?> Inventory System. All rights reserved. Powered by "Ahnaf IT"
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ===== Sidebar Mobile =====
function toggleSidebar() {
  var sidebar = document.getElementById('sidebar');
  var overlay = document.getElementById('sidebarOverlay');
  sidebar.classList.toggle('active');
  overlay.classList.toggle('active');
}

function closeSidebar() {
  document.getElementById('sidebar').classList.remove('active');
  document.getElementById('sidebarOverlay').classList.remove('active');
}

// ===== Sidebar Desktop Collapse =====
function toggleCollapse() {
  var sidebar = document.getElementById('sidebar');
  var mainContent = document.querySelector('.main-content');
  var icon = document.getElementById('collapseBtn').querySelector('i');

  sidebar.classList.toggle('collapsed');
  mainContent.classList.toggle('expanded');

  if (sidebar.classList.contains('collapsed')) {
    icon.className = 'fas fa-chevron-right';
    localStorage.setItem('sidebarCollapsed', '1');
  } else {
    icon.className = 'fas fa-bars';
    localStorage.setItem('sidebarCollapsed', '0');
  }
}

// Page load এ collapsed state মনে রাখো
document.addEventListener('DOMContentLoaded', function() {
  if (window.innerWidth > 768) {
    var collapsed = localStorage.getItem('sidebarCollapsed');
    if (collapsed === '1') {
      var sidebar = document.getElementById('sidebar');
      var mainContent = document.querySelector('.main-content');
      var btn = document.getElementById('collapseBtn');
      if (sidebar) sidebar.classList.add('collapsed');
      if (mainContent) mainContent.classList.add('expanded');
      if (btn) btn.querySelector('i').className = 'fas fa-chevron-right';
    }
  }
});

// Window resize
window.addEventListener('resize', function() {
  if (window.innerWidth > 768) {
    closeSidebar();
  }
});

// ===== CSV Download =====
function downloadCSV(tableId, filename) {
  var table = document.getElementById(tableId);
  if (!table) return;

  var rows = table.querySelectorAll('tr');
  var csv = [];

  rows.forEach(function(row) {
    var cols = row.querySelectorAll('th, td');
    var rowData = [];
    cols.forEach(function(col) {
      if (!col.classList.contains('no-print')) {
        var text = col.innerText
          .replace(/"/g, '""')
          .replace(/\n/g, ' ')
          .trim();
        rowData.push('"' + text + '"');
      }
    });
    if (rowData.length > 0) csv.push(rowData.join(','));
  });

  var csvContent = '\uFEFF' + csv.join('\n');
  var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  var link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = filename + '_' + new Date().toISOString().slice(0,10) + '.csv';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

// ===== Print =====
function printArea(areaId) {
  var content = document.getElementById(areaId).innerHTML;
  var original = document.body.innerHTML;
  document.body.innerHTML = '<div style="padding:20px">' + content + '</div>';
  window.print();
  document.body.innerHTML = original;
  window.location.reload();
}
</script>
</body>
</html>