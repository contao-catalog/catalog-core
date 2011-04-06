
<div id="tl_buttons">
<a href="<?php echo $this->href; ?>" class="header_back" title="<?php echo $this->title; ?>"><?php echo $this->button; ?></a>
</div>

<div id="tl_catalogtagsmaintenance">

<h2 class="sub_headline"><?php echo $this->tagsHeadline; ?></h2>
<?php if ($this->tagsMessage): ?>

<div class="tl_message">
<p class="tl_error"><?php echo $this->tagsMessage; ?></p>
</div>
<?php endif; ?>

<form action="<?php echo $this->action; ?>" class="tl_form" method="post">
<div class="tl_formbody_edit">
<input type="hidden" name="act" value="tags" />
<div class="fields">
<?php echo $this->tagsContent; ?>
</div>
<div class="tl_submit_container">
<input type="submit" id="tags" class="tl_submit" value="<?php echo $this->tagsSubmit; ?>" /> 
</div>

</div>
</form>

</div>