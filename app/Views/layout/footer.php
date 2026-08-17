<script>
function openModal(id){
  var el = document.getElementById(id);
  if(el) el.classList.add('active');
}
function closeModal(id){
  var el = document.getElementById(id);
  if(el) el.classList.remove('active');
}

document.querySelectorAll('.toggle-pass').forEach(function(btn){
  btn.addEventListener('click', function(){
    var input = this.parentElement.querySelector('input');
    if(!input) return;
    var isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    this.classList.toggle('show-pass');
  });
});

document.querySelectorAll('.tabs-switch').forEach(function(group){
  group.querySelectorAll('button').forEach(function(btn){
    btn.addEventListener('click', function(){
      group.querySelectorAll('button').forEach(function(b){ b.classList.remove('active'); });
      this.classList.add('active');
    });
  });
});

document.querySelectorAll('.tx-type-toggle').forEach(function(group){
  group.querySelectorAll('button').forEach(function(btn){
    btn.addEventListener('click', function(){
      group.querySelectorAll('button').forEach(function(b){ b.classList.remove('active'); });
      this.classList.add('active');
    });
  });
});

document.querySelectorAll('.modal-trigger').forEach(function(btn){
  btn.addEventListener('click', function(){
    var target = document.getElementById(this.dataset.modal);
    if(target) target.classList.add('active');
  });
});

document.querySelectorAll('.ku-modal-close, .ku-modal-overlay').forEach(function(el){
  el.addEventListener('click', function(e){
    if(e.target === this || this.classList.contains('ku-modal-close')){
      var overlay = this.closest('.ku-modal-overlay');
      if(overlay) overlay.classList.remove('active');
    }
  });
});

document.querySelectorAll('.ku-search-box input').forEach(function(input){
  input.addEventListener('keyup', function(){
    var q = this.value.toLowerCase();
    var content = this.closest('.content');
    if(!content) return;
    content.querySelectorAll('.ku-table').forEach(function(table){
      table.querySelectorAll('tbody tr').forEach(function(row){
        var text = row.textContent.toLowerCase();
        row.style.display = text.indexOf(q) > -1 ? '' : 'none';
      });
    });
  });
});

// Mobile sidebar toggle
(function(){
  var hamburger = document.getElementById('hamburger-btn');
  var sidebar = document.querySelector('.sidebar');
  var overlay = document.getElementById('sidebar-overlay');
  if (!hamburger || !sidebar || !overlay) return;
  function toggleSidebar() {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('open');
  }
  hamburger.addEventListener('click', toggleSidebar);
  overlay.addEventListener('click', toggleSidebar);
})();
</script>
</body>
</html>
