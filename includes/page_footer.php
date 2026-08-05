<?php
/**
 * includes/page_footer.php
 * Reusable footer that closes the layout HTML structure.
 * Usage: require_once '../includes/page_footer.php';
 * $jsExtra — optional extra JS to inject before </body>
 */
?>
    </main>
    <!-- End page content -->
  </div>
  <!-- End main content -->
</div>
<!-- End app layout -->

<script src="../assets/js/app.js"></script>
<?php if (!empty($jsExtra)) echo $jsExtra; ?>
</body>
</html>
