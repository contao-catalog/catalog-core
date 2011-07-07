
<?php if ($this->filterOptions): ?>
<div class="filter_group">

<?php if ($this->filter_headline): ?>
<<?php echo $this->filter_hl; ?>><?php echo $this->filter_headline; ?></<?php echo $this->filter_hl; ?>>
<?php endif; ?>

<?php foreach($this->filterOptions as $filterOption): ?>
<?php echo $filterOption; ?>
<?php endforeach; ?>

</div>
<?php endif; ?>


<?php if ($this->rangeOptions): ?>
<div class="range_group">

<?php if ($this->range_headline): ?>
<<?php echo $this->range_hl; ?>><?php echo $this->range_headline; ?></<?php echo $this->range_hl; ?>>
<?php endif; ?>

<form method="post" id="<?php echo $this->table; ?>_range" action="<?php echo $this->action; ?>">	
<div class="range">
<?php if((version_compare(VERSION, '2.10', '>='))): ?><input type="hidden" name="REQUEST_TOKEN" value="{{request_token}}"><?php endif; ?>
<input type="hidden" name="FORM_SUBMIT" value="<?php echo $this->table; ?>" />
<input type="hidden" name="FORM_DATA" value="range" />
<?php foreach($this->rangeOptions as $rangeOption): ?>
<?php echo $rangeOption; ?>
<?php endforeach; ?>
</div>
</form>

</div>
<?php endif; ?>


<?php if ($this->dateOptions): ?>
<div class="date_group">

<?php if ($this->date_headline): ?>
<<?php echo $this->date_hl; ?>><?php echo $this->date_headline; ?></<?php echo $this->date_hl; ?>>
<?php endif; ?>

<form method="post" id="<?php echo $this->table; ?>_date" action="<?php echo $this->action; ?>">	
<div class="date">
<input type="hidden" name="FORM_SUBMIT" value="<?php echo $this->table; ?>" />
<input type="hidden" name="FORM_DATA" value="date" />
<?php foreach($this->dateOptions as $dateOption): ?>
<?php echo $dateOption; ?>
<?php endforeach; ?>
</div>
</form>

</div>
<?php endif; ?>


<?php if ($this->searchOptions): ?>
<div class="search_group">

<?php if ($this->search_headline): ?>
<<?php echo $this->search_hl; ?>><?php echo $this->search_headline; ?></<?php echo $this->search_hl; ?>>
<?php endif; ?>

<form method="post" id="<?php echo $this->table; ?>_search" action="<?php echo $this->action; ?>"> 
<div class="search">
<input type="hidden" name="FORM_SUBMIT" value="<?php echo $this->table; ?>" />
<input type="hidden" name="FORM_DATA" value="search" />
<?php echo $this->searchOptions; ?>
</div>
</form> 

</div>
<?php endif; ?>


<?php if ($this->sortOptions): ?>
<div class="sort_group">

<?php if ($this->sort_headline): ?>
<<?php echo $this->sort_hl; ?>><?php echo $this->sort_headline; ?></<?php echo $this->sort_hl; ?>>
<?php endif; ?>

<div class="sort">
<?php echo $this->sortOptions; ?>
</div>

</div>
<?php endif; ?>

<div class="clearall">
<a href="<?php echo $this->clearall; ?>" title="<?php echo $this->clearallText; ?>"><?php echo $this->clearallText; ?></a>
</div>
