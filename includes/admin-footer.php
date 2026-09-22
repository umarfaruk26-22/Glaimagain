<?php
/**
 * GLAIMAGAIN - Admin Footer Template
 */
?>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/admin.js?v=<?= time() ?>"></script>
    <?php if (isset($adminExtraScripts)) echo $adminExtraScripts; ?>
</body>
</html>
