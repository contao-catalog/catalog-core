<?php 

/***
 * Sample Custom Widget file
 * This sample shows how to re-create the range widget yourself with your own
 * custom code (most complicated). To see the data structure, uncomment the print_r() 
 * Full Custom Widget file
 * To see the data structure, uncomment the print_r() 
 */
?>

<?php //print_r($this->widgets); ?>
<?php 
/***
 * <pre><?php print_r($this->widgets); ?></pre>
 * <pre><?php print_r(deserialize($this->widgets['sort'][0]['options'])); ?></pre>
 * <pre><?php print_r($filterOption); ?></pre>
*/ 
?>

<?php
/***
 * Filter Option
 */
if ($this->filterOptions): ?>
<div class="filter_group">

<?php if ($this->filter_headline): ?>
<<?php echo $this->filter_hl; ?>><?php echo $this->filter_headline; ?></<?php echo $this->filter_hl; ?>>
<?php endif; ?>

<?php foreach($this->widgets['filter'] as $filterWidget): ?>
<div class="widget <?php echo $filterWidget['id']; ?>">
<label for="ctrl_<?php echo $filterWidget['id']; ?>"><?php echo $filterWidget['label']; ?></label>

<?php if ($filterWidget['inputType'] == 'select'): ?>

<select class="<?php echo ($filterWidget['multiple'] ? 'multiselect' : 'select'); ?>" id="ctrl_<?php echo $filterWidget['id']; ?>"<?php echo ($filterWidget['multiple'] ? ' multiple="multiple"' : ''); ?> <?php echo $filterWidget['attributes']; ?> name="<?php echo ($filterWidget['multiple'] ? $filterWidget['name'].'[]' : $filterWidget['name']); ?>">
<?php $i = 0; foreach(deserialize($filterWidget['options']) as $filterOption): ?>
<option <?php if ($filterOption['selected']) echo 'selected="selected" '; ?>value="<?php echo $filterOption['value']; ?>"><?php echo ($i==0 ? 'Beliebig' : $filterOption['label']); ?></option>
<?php $i++; endforeach; ?>
</select>

<?php elseif (($filterWidget['inputType'] == 'radio') || ($filterWidget['inputType'] == 'checkbox')): ?>

<div id="ctrl_<?php echo $filterWidget['id']; ?>" class="radio_container">
<?php foreach(deserialize($filterWidget['options']) as $filterOption): ?>
<span><input id="opt_<?php echo $filterWidget['id']; ?>_<?php echo $filterOption['id']; ?>" <?php echo $filterWidget['attributes']; ?><?php if ($filterOption['selected']) echo ' checked="checked" '; ?> <?php echo ($filterWidget['multiple'] ? 'class="checkbox" type="checkbox"' : 'class="radio" type="radio"'); ?> name="<?php echo ($filterWidget['multiple'] ? $filterWidget['name'].'[]' : $filterWidget['name']); ?>" value="<?php echo $filterOption['value']; ?>" /><label for="opt_<?php echo $filterWidget['id']; ?>_<?php echo $filterOption['id']; ?>"><?php echo $filterOption['label']; ?></label></span>
<?php endforeach; ?>
</div>

<?php elseif ($filterWidget['inputType'] == 'list'): ?>

<ul class="list">
<?php $i = 0; foreach(deserialize($filterWidget['options']) as $filterOption): ?>
<li class="option <?php echo ($i==0 ? 'list_none' : 'list_id-'.$filterOption['id']); ?><?php if ($filterOption['selected']) echo ' active'; ?>"><a href="<?php echo $filterOption['value']; ?>" title="<?php echo $filterOption['label']; ?>"><?php echo $filterOption['label']; ?> (<?php echo $filterOption['resultcount']; ?>)</a></li>
<?php $i++; endforeach; ?>
</ul>

<?php endif; ?>

</div>
<?php endforeach; ?>

</div>
<?php endif; ?>

<?php 
/***
 * Range Option
 */
if ($this->rangeOptions): ?>
<div class="range_group">

<?php if ($this->range_headline): ?>
<<?php echo $this->range_hl; ?>><?php echo $this->range_headline; ?></<?php echo $this->range_hl; ?>>
<?php endif; ?>

<form method="post" id="<?php echo $this->table; ?>_range" action="<?php echo $this->action; ?>">
<div class="range">
<?php if((version_compare(VERSION, '2.10', '>='))): ?><input type="hidden" name="REQUEST_TOKEN" value="{{request_token}}"><?php endif; ?>
<input type="hidden" name="FORM_SUBMIT" value="<?php echo $this->table; ?>" />
<input type="hidden" name="FORM_DATA" value="range" />
<?php foreach($this->widgets['range'] as $rangeWidget): ?>
<div class="widget <?php echo $rangeWidget['id']; ?>">
<label for="ctrl_<?php echo $rangeWidget['id']; ?>"><?php echo $rangeWidget['label']; ?></label>
<div id="ctrl_<?php echo $rangeWidget['id']; ?>">
<?php for($i=0;$i<=1;$i++): ?>
<input type="text" name="<?php echo $rangeWidget['name']; ?>[]" id="ctrl_<?php echo $rangeWidget['id'].'_'.$i; ?>" class="text" value="<?php echo $rangeWidget['value'][$i]; ?>" />
<?php if ($rangeWidget['datepicker']): ?>
<script type="text/javascript"><!--//--><![CDATA[//><!--
window.addEvent('domready', function() {
<?php echo sprintf($rangeWidget['datepicker'],'ctrl_'.$rangeWidget['id'].'_'.$i); ?>
});
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

<?php 
/***
 * Sort Option
 */
if ($this->sortOptions): ?>
<div class="sort_group">

<?php if ($this->sort_headline): ?>
<<?php echo $this->sort_hl; ?>><?php echo $this->sort_headline; ?></<?php echo $this->sort_hl; ?>>
<?php endif; ?>

<div class="sort">
<div class="widget <?php echo $this->widgets['sort']['id']; ?>">
<?php if ($this->widgets['sort']['inputType'] == 'select'): ?>

<select class="select" id="ctrl_<?php echo $this->widgets['sort']['id']; ?>" <?php echo $this->widgets['sort']['attributes']; ?> name="<?php echo $this->widgets['sort']['name']; ?>">
<?php $i = 0; foreach(deserialize($this->widgets['sort']['options']) as $sortOption): ?>
<option <?php if ($sortOption['selected']) echo 'selected="selected" '; ?>value="<?php echo $sortOption['value']; ?>"><?php echo $sortOption['label']; ?></option>
<?php $i++; endforeach; ?>
</select>

<?php elseif ($this->widgets['sort']['inputType'] == 'radio'): ?>
<div class="ctrl_<?php echo $this->widgets['sort']['id']; ?>" class="radio_container">
<?php foreach(deserialize($this->widgets['sort']['options']) as $sortOption): ?>
<span><input id="opt_<?php echo $this->widgets['sort']['id']; ?>_<?php echo $sortOption['id']; ?>" <?php echo $this->widgets['sort']['attributes']; ?><?php if ($sortOption['selected']) echo ' checked="checked" '; ?>class="radio" type="radio" name="<?php echo $this->widgets['sort']['name']; ?>" value="<?php echo $sortOption['value']; ?>" /><label for="opt_<?php echo $this->widgets['sort']['id']; ?>_<?php echo $sortOption['id']; ?>"><?php echo $sortOption['label']; ?></label></span>
<?php endforeach; ?>
</div>

<?php elseif ($this->widgets['sort']['inputType'] == 'list'): ?>

<div id="ctrl_<?php echo $this->widgets['sort']['id']; ?>" class="list_container">
<ul class="list">
<?php $i = 0; foreach(deserialize($this->widgets['sort']['options']) as $sortOption): ?>
<li class="option <?php echo ($i==0 ? 'list_none' : 'list_id-'.$sortOption['id']); ?><?php if ($sortOption['selected']) echo ' active'; ?>"><a href="<?php echo $sortOption['value']; ?>" title="<?php echo $sortOption['label']; ?>"><?php echo $sortOption['label']; ?></a></li>
<?php $i++; endforeach; ?>
</ul>
</div>

<?php endif; ?>
</div>
</div>

</div>
<?php endif; ?>


<?php 
/***
 * Date Option
 */
if ($this->dateOptions): ?>
<div class="date_group">

<?php if ($this->date_headline): ?>
<<?php echo $this->date_hl; ?>><?php echo $this->date_headline; ?></<?php echo $this->date_hl; ?>>
<?php endif; ?>

<form method="post" id="<?php echo $this->table; ?>_date" action="<?php echo $this->action; ?>">	
<div class="date">
<?php if((version_compare(VERSION, '2.10', '>='))): ?><input type="hidden" name="REQUEST_TOKEN" value="{{request_token}}"><?php endif; ?>
<input type="hidden" name="FORM_SUBMIT" value="<?php echo $this->table; ?>" />
<input type="hidden" name="FORM_DATA" value="date" />
<?php foreach($this->widgets['date'] as $dateWidget): ?>

<?php if (!($dateWidget['inputType'] == 'none')): ?>
<div class="widget <?php echo $dateWidget['id']; ?>">
<label for="ctrl_<?php echo $dateWidget['id']; ?>"><?php echo $dateWidget['label']; ?></label>
<?php endif; ?>

<?php if ($dateWidget['inputType'] == 'select'): ?>

<select class="select" id="ctrl_<?php echo $dateWidget['id']; ?>" <?php echo $dateWidget['attributes']; ?> name="<?php echo $dateWidget['name']; ?>">
<?php $i = 0; foreach(deserialize($dateWidget['options']) as $dateOption): ?>
<option <?php if ($dateOption['selected']) echo 'selected="selected" '; ?>value="<?php echo $dateOption['value']; ?>"><?php echo ($i==0 ? 'Beliebig' : $dateOption['label']); ?></option>
<?php $i++; endforeach; ?>
</select>

<?php elseif (($dateWidget['inputType'] == 'radio') || ($dateWidget['inputType'] == 'checkbox')): ?>

<div id="ctrl_<?php echo $dateWidget['id']; ?>" class="radio_container">
<?php foreach(deserialize($dateWidget['options']) as $dateOption): ?>
<span><input id="opt_<?php echo $dateWidget['id']; ?>_<?php echo $dateOption['id']; ?>" <?php echo $dateWidget['attributes']; ?><?php if ($dateOption['selected']) echo ' checked="checked" '; ?> class="radio" type="radio" name="<?php echo $dateWidget['name']; ?>" value="<?php echo $dateOption['value']; ?>" /><label for="opt_<?php echo $dateWidget['id']; ?>_<?php echo $dateOption['id']; ?>"><?php echo $dateOption['label']; ?></label></span>
<?php endforeach; ?>
</div>

<?php elseif ($dateWidget['inputType'] == 'list'): ?>

<div id="ctrl_<?php echo $dateWidget['id']; ?>" class="list_container">
<ul class="list">
<?php $i = 0; foreach(deserialize($dateWidget['options']) as $dateOption): ?>
<li class="option <?php echo ($i==0 ? 'list_none' : 'list_id-'.$dateOption['id']); ?><?php if ($dateOption['selected']) echo ' active'; ?>"><a href="<?php echo $dateOption['value']; ?>" title="<?php echo $dateOption['label']; ?>"><?php echo $dateOption['label']; ?></a></li>
<?php $i++; endforeach; ?>
</ul>
</div>

<?php endif; ?>

<?php if (!($dateWidget['inputType'] == 'none')): ?>
</div>
<?php endif; ?>

<?php endforeach; ?>
</div>
</form>

</div>
<?php endif; ?>


<?php 
/***
 * Search Option
 */
if ($this->searchOptions): ?>
<div class="search_group">

<?php if ($this->search_headline): ?>
<<?php echo $this->search_hl; ?>><?php echo $this->search_headline; ?></<?php echo $this->search_hl; ?>>
<?php endif; ?>

<form method="post" id="<?php echo $this->table; ?>_search" action="<?php echo $this->action; ?>"> 
<div class="search">
<?php if((version_compare(VERSION, '2.10', '>='))): ?><input type="hidden" name="REQUEST_TOKEN" value="{{request_token}}"><?php endif; ?>
<input type="hidden" name="FORM_SUBMIT" value="<?php echo $this->table; ?>" />
<input type="hidden" name="FORM_DATA" value="search" />
<div class="widget <?php echo $this->widgets['search']['id']; ?>">
<input type="text" value="<?php echo $this->widgets['search']['value']; ?>" class="text" id="ctrl_<?php echo $this->widgets['search']['id']; ?>" name="<?php echo $this->widgets['search']['name']; ?>" /> 
<input type="submit" value="<?php echo $this->widgets['search']['slabel']; ?>" class="submit" id="ctrl_<?php echo $this->widgets['search']['id']; ?>_submit" />
</div>
</div>
</form> 

</div>
<?php endif; ?>

<div class="clearall">
<a href="<?php echo $this->clearall; ?>" title="<?php echo $this->clearallText; ?>"><?php echo $this->clearallText; ?></a>
</div>
