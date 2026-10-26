document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('ProjectsUserNew');
    var existing = document.getElementById('ProjectsUserExisting');
    var fresh = document.getElementById('ProjectsUserNewFields');

    if (!toggle || !existing || !fresh) {
        return;
    }

    function update() {
        existing.style.display = toggle.checked ? 'none' : '';
        fresh.style.display = toggle.checked ? '' : 'none';
    }

    toggle.addEventListener('change', update);
    update();
});
