</div>
<script>
	// Auto fecho de alerts após 4s
	document.querySelectorAll('.auto-dismiss').forEach((el) => {
		setTimeout(() => {
			el.classList.add('fade');
			el.style.transition = 'opacity 0.4s ease';
			el.style.opacity = '0';
			setTimeout(() => el.remove(), 400);
		}, 4000);
	});

	// Marcar item ativo da barra de navegação
	(() => {
		const page = new URLSearchParams(window.location.search).get('page') || 'painel';
		document.querySelectorAll('.navbar .nav-link').forEach((link) => {
			const href = link.getAttribute('href') || '';
			const isHome = page === 'painel' && (href === 'index.php' || href === './');
			const matches = href.includes(`page=${page}`);
			if (isHome || matches) {
				link.classList.add('fw-semibold', 'text-white');
			}
		});
	})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
