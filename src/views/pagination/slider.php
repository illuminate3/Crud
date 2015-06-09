<?php
	$presenter = new Illuminate\Pagination\BootstrapPresenter($paginator);
?>

<?php if ($paginator->getLastPage() > 1): ?>
	<div class="row">
		<ul class="pagination pull-right">
			<?php echo $presenter->render(); ?>
		</ul>
	</div>
<?php endif; ?>