
<div id="tl_buttons">
<a href="<?php echo $this->href; ?>" class="header_back" title="<?php echo $this->title; ?>"><?php echo $this->button; ?></a>
</div>

<h2 class="sub_headline"><?php echo $this->tagsHeadline; ?></h2>

<div id="tl_rebuild_tags">

<p id="catalogtags_note"><?php echo $this->note; ?></p>
<p id="catalogtags_loading" style="display:none;"><?php echo $this->loading; ?></p>
<p id="catalogtags_complete" style="display:none;"><?php echo $this->complete; ?></p>

<?php echo $this->content; ?>

</div>

<script type="text/javascript">
<!--//--><![CDATA[//><!--
window.addEvent('domready', function() {
  $('catalogtags_note').setStyle('display', 'none');
  $$('h2.sub_headline').setStyle('display', 'none');
  $('catalogtags_loading').setStyle('display', 'block');
});
window.addEvent('load', function() {
  $('catalogtags_loading').setStyle('display', 'none');
  $('catalogtags_complete').setStyle('display', 'block');
});
//--><!]]>
</script>

<form action="<?php echo $this->href; ?>" class="tl_form" method="get">
<div class="tl_submit_container">
<?php if(version_compare(VERSION, '2.10', '>=')): ?><input type="hidden" name="REQUEST_TOKEN" value="<?php echo REQUEST_TOKEN; ?>"><?php endif; ?>
<input type="hidden" name="do" value="catalog" />
<input type="hidden" name="key" value="maintenance" />
<input type="submit" id="index" class="tl_submit" value="<?php echo $this->tagsContinue; ?>" /> 
</div>
</form>
