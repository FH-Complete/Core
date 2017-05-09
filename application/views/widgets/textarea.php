<?php Widget::printStartBlock(${Widget::HTML_ARG_NAME}); ?>
<?php
	if (isset(${Widget::HTML_ARG_NAME}[UDFWidgetTpl::LABEL]))
	{
?>
	<label for="<?php echo ${Widget::HTML_ARG_NAME}[Widget::HTML_ID]; ?>">
		<?php echo ${Widget::HTML_ARG_NAME}[UDFWidgetTpl::LABEL]; ?>
	</label>
<?php
	}
?>
<textarea
	<?php Widget::printAttribute(${Widget::HTML_ARG_NAME}, Widget::HTML_ID); ?>
	<?php Widget::printAttribute(${Widget::HTML_ARG_NAME}, Widget::HTML_NAME); ?>
	<?php Widget::printAttribute(${Widget::HTML_ARG_NAME}, TextareaWidget::ROWS); ?>
	<?php Widget::printAttribute(${Widget::HTML_ARG_NAME}, TextareaWidget::COLS); ?>
	<?php Widget::printAttribute(${Widget::HTML_ARG_NAME}, UDFWidgetTpl::REQUIRED); ?>
	<?php Widget::printAttribute(${Widget::HTML_ARG_NAME}, UDFWidgetTpl::PLACEHOLDER); ?>
	<?php Widget::printAttribute(${Widget::HTML_ARG_NAME}, UDFWidgetTpl::REGEX); ?>
	<?php Widget::printAttribute(${Widget::HTML_ARG_NAME}, UDFWidgetTpl::TITLE); ?>
>
<?php echo ${TextareaWidget::TEXT}; ?>
</textarea>
<?php Widget::printEndBlock(${Widget::HTML_ARG_NAME}); ?>