<?php mgAddMeta('<link type="text/css" href="'.SCRIPT.'standard/css/layout.search.css" rel="stylesheet"/>'); ?>
<?php mgAddMeta('<script type="text/javascript" src="'.SCRIPT.'standard/js/layout.search.js"></script>'); ?>

<div class="mg-search-block">
    <form method="GET" action="<?php echo SITE?>/catalog" class="search-form">
        <input type="search" autocomplete="off" name="search" class="search-field" placeholder="Найти товар" value="<?php echo urldecode($_GET['search']);?>">
            <input type="submit" class="search-button" value="">
	</form>
	<div class="wraper-fast-result">
		<div class="fastResult">

		</div>
	</div>
</div>