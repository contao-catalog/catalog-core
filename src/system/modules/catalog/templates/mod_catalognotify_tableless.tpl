<!-- indexer::stop -->
<div class="<?php echo $this->class; ?> block"<?php echo $this->cssID; ?><?php if ($this->style): ?> style="<?php echo $this->style; ?>"<?php endif; ?>>
<?php if ($this->headline): ?>

<<?php echo $this->hl; ?>><?php echo $this->headline; ?></<?php echo $this->hl; ?>>
<?php endif; ?>

<?php if ($this->error): ?>
<p class="error"><?php echo $this->error; ?></p>

<?php else: ?>
<form action="<?php echo $this->action; ?>" method="post">
<div class="formbody">
<input type="hidden" name="FORM_SUBMIT" value="<?php echo $this->formId; ?>" />

<?php echo $this->fields; ?>

<div class="submit_container <?php echo $this->rowLast; ?>"><input type="submit" class="submit" value="<?php echo $this->slabel; ?>" /></div>

</div>
</form>

<?php endif; ?>

<?php if ($this->hasError): ?>

<script type="text/javascript">
<!--//--><![CDATA[//><!--
window.scrollTo(null, ($('<?php echo $this->formId; ?>').getElement('p.error').getPosition().y - 20));
//--><!]]>
</script>
<?php endif; ?>

</div>
<!-- indexer::continue -->