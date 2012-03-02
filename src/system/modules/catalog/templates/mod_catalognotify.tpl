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
<table cellspacing="0" cellpadding="0" summary="Table holds form input fields">
<?php echo $this->fields; ?>
  <tr class="<?php echo $this->rowLast; ?>">
    <td class="col_0 col_first">&nbsp;</td>
    <td class="col_1 col_last"><div class="submit_container"><input type="submit" class="submit" value="<?php echo $this->slabel; ?>" /></div></td>
  </tr>
</table>
</div>
</form>

<?php endif; ?>

</div>
<!-- indexer::continue -->