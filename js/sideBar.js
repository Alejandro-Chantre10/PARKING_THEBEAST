fetch("../views/sideBar.html")
    .then(response => response.text())
    .then(data => {
        document.getElementById("menu-container").innerHTML = data;

        const toggleMenu = document.getElementById('toggle-menu');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const showSidebarBtn = document.getElementById('show-sidebar-btn');

        toggleMenu.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
            mainContent.classList.toggle('expanded');
            showSidebarBtn.classList.toggle('hidden'); // Mostrar botón flotante cuando el sidebar esté oculto
        });

        showSidebarBtn.addEventListener('click', () => {
            sidebar.classList.remove('hidden');
            mainContent.classList.remove('expanded');
            showSidebarBtn.classList.add('hidden'); // Ocultar el botón flotante nuevamente
        });
    });
