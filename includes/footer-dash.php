<?php
if (!isset($path_prefix)) {
    $path_prefix = '';
    if (file_exists('includes/config.php')) {
        $path_prefix = '';
    } elseif (file_exists('../includes/config.php')) {
        $path_prefix = '../';
    } elseif (file_exists('../../includes/config.php')) {
        $path_prefix = '../../';
    }
}
?>
<!-- Global JS -->
<script>
  // Expose path prefix so all JS files resolve API URLs correctly
  window.pathPrefix = '<?php echo $path_prefix; ?>';
</script>
<script src="<?php echo $path_prefix; ?>js/main.js"></script>
<?php if (isset($_SESSION['user_id'])): ?>
<script src="<?php echo $path_prefix; ?>js/realtime.js"></script>
<?php endif; ?>

