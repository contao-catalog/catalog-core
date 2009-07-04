<?php echo $this->rteConfig; ?>
 
<form action="<?php echo $this->action; ?>" method="post">
<div class="formbody">
<input type="hidden" name="FORM_SUBMIT" value="<?php echo $this->formId; ?>" />
<table cellspacing="0" cellpadding="0" summary="Table holds form input fields">
<?php echo $this->field; ?>
  <tr class="<?php echo $this->rowLast; ?>">
    <td class="col_0 col_first">&nbsp;</td>
    <td class="col_1 col_last"><div class="submit_container"><input type="submit" class="submit" value="<?php echo $this->slabel; ?>" /></div></td>
  </tr>
</table>
</div>
</form>
