    </main>
    
    <footer style="text-align: center; padding: 2rem; color: #666; background: #fff; margin-top: 2rem;">
        <p>&copy; <?php echo date('Y'); ?> 11 Seconds Quiz Game - Admin Center</p>
    </footer>
    
    <script>
        // Add some basic interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading states for buttons
            const buttons = document.querySelectorAll('button, .btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (this.type === 'submit' || this.classList.contains('btn-primary')) {
                        this.style.opacity = '0.7';
                        this.style.cursor = 'wait';
                    }
                });
            });
        });
    </script>
</body>
</html>
