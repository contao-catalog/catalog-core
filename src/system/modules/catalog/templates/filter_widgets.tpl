<?php 

/***
 * Sample Custom Widget file
 * This sample shows how to re-create the range widget yourself with your own
 * custom code (most complicated). To see the data structure, uncomment the print_r() 
 */
 
?>

<?php //print_r($this->widgets); ?>

<?php //print_r(deserialize($this->widgets['filter'][0]['options'])); ?>

<?php if ($this->rangeOptions): ?>
<div class="range_group">

<?php if ($this->range_headline): ?>
<<?php echo $this->range_hl; ?>><?php echo $this->range_headline; ?></<?php echo $this->range_hl; ?>>
<?php endif; ?>

<form method="post" id="<?php echo $this->table; ?>_range" action="<?php echo $this->action; ?>">	
<div class="range">
<input type="hidden" name="FORM_SUBMIT" value="<?php echo $this->table; ?>" />
<input type="hidden" name="FORM_DATA" value="range" />
<?php foreach($this->widgets['range'] as $rangeWidget): ?>
<div class="widget <?php echo $rangeWidget['id']; ?>">
<h3><label for="ctrl_<?php echo $rangeWidget['id']; ?>"><?php echo $rangeWidget['label']; ?></label></h3>
<div id="ctrl_<?php echo $rangeWidget['id']; ?>">
<?php for($i=0;$i<=1;$i++): ?>
<input type="<?php echo ($i==1 ? 'text' : 'hidden'); ?>" name="<?php echo $rangeWidget['name']; ?>[]" id="ctrl_<?php echo $rangeWidget['id'].'_'.$i; ?>" class="text" value="<?php echo $rangeWidget['value'][$i]; ?>" />
<?php if ($rangeWidget['datepicker']): ?>
<script type="text/javascript"><!--//--><![CDATA[//><!--
<?php echo sprintf($rangeWidget['datepicker'],'ctrl_'.$rangeWidget['id'].'_'.$i); ?>
//--><!]]></script>
<?php endif; ?>
<?php endfor; ?>
<input type="submit" id="ctrl_<?php echo $rangeWidget['id']; ?>_submit" class="submit" value="<?php echo $rangeWidget['slabel']; ?>" /></div>
</div>
<?php endforeach; ?>
</div>
</form>

</div>
<?php endif; ?>



<div class="clearall">
<a href="<?php echo $this->clearall; ?>" title="<?php echo $this->clearallText; ?>"><?php echo $this->clearallText; ?></a>
</div>
