<?php
App::uses('LilAppModel', 'Lil.Model');
class Attachment extends LilAppModel {

	var $name = 'Attachment';

	var $actsAs = array(
		'LilUpload.LilUpload' => array(
			'allowedMime'       => '*',
			'allowedExt'        => '*',
			'dirFormat'         => '',
			'fileFormat'        => '{HASH}',
			'titleField'        => 'original',
			'titleFormat'       => '{$full_name}',
			'sizeField'         => 'filesize',
			
			'overwriteExisting' => false,
			'mustUploadFile'    => false,
		)
	);
/**
 * __construct function
 *
 * @param mixed $id
 * @param mixed $table
 * @param mixed $ds
 * @access private
 * @return void
 */
	function __construct($id = false, $table = null, $ds = null)	{
		$this->actsAs['LilUpload.Lilupload']['baseDir'] = $this->getTargetFolder('uploads');
		parent::__construct($id, $table, $ds);
	}
/**
 * getTargetFolder function
 *
 * @param mixed $type
 * @access public
 * @return void
 */
	function getTargetFolder($type = 'thumbs') {
		if ($type == 'thumbs') {
			return IMAGES . 'thumbs' . DS;
		} else {
			return APP . 'uploads' . DS;
		}
 	}
}