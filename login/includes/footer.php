<!-- / footer -->
</section>
<!--main content end-->
</section>


<script src="<?PHP echo $website_url; ?>/js/bootstrap.js"></script>
<script src="<?PHP echo $website_url; ?>/js/jquery.dcjqaccordion.2.7.js"></script>
<script src="<?PHP echo $website_url; ?>/js/scripts.js"></script>
<script src="<?PHP echo $website_url; ?>/js/jquery.slimscroll.js"></script>
<script src="<?PHP echo $website_url; ?>/js/jquery.nicescroll.js"></script>
<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="js/flot-chart/excanvas.min.js"></script><![endif]-->
<script src="<?PHP echo $website_url; ?>/js/jquery.scrollTo.js"></script>
<!-- morris JavaScript -->
<!-- calendar -->
<script type="text/javascript" src="<?PHP echo $website_url; ?>/js/monthly.js"></script>
<script type="text/javascript">
$(window).load(function() {

  $('#mycalendar').monthly({
    mode: 'event',

  });

  $('#mycalendar2').monthly({
    mode: 'picker',
    target: '#mytarget',
    setWidth: '250px',
    startHidden: true,
    showTrigger: '#mytarget',
    stylePast: true,
    disablePast: true
  });

  switch (window.location.protocol) {
    case 'http:':
    case 'https:':
      // running on a server, should be good.
      break;
    case 'file:':
      alert('Just a heads-up, events will not work when run locally.');
  }
});

</script>
<!-- //calendar -->
</body>
<?php if ($VENDASTA_DATA_URL != ''): ?>

<script src="https://www.cdnstyles.com/static/product_navbar/v1/product_navbar.js" data-url="<?= $VENDASTA_DATA_URL ?>"
  data-account-id=" <?= $_SESSION['account_id'] ?>" data-app-id="<?= $PRODUCT_ID ?>"
  target-element-class=" top-nav-element">
</script>

<?php endif; ?>

</html>