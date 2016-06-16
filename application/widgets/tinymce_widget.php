<?php

/*
 * TinyMCE widget
 */
class tinymce_widget extends Widget 
{
    public function display($data) 
	{
		if (! isset($data['selector']))
			$data['selector'] = 'textarea';
		if (! isset($data['plugins']))
			$data['plugins'] = '
				"advlist autolink lists link image charmap print preview anchor",
        		"searchreplace visualblocks code fullscreen",
        		"insertdatetime media table contextmenu paste"';
		if (! isset($data['toolbar']))
			$data['toolbar'] = 'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image';

        $this->view('widgets/tinymce', $data);
    }
    
}
