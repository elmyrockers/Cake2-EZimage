<?php

/*=====================================================
=            EZimage Component For CakePHP            =
=====================================================/
	- initialize(Controller $controller )

	- upload( $uploadData, $uploadDir, $filename = NULL, $returnObject = FALSE )
	- file( $filepath )

	- crop( $width = 0, $height = 0, $x = 0, $y = 0 )
	- cropAtCenter( $width = 0, $height = 0 )
	- resize( $width = 0, $height = 0, $proportional = TRUE )
	- watermark( $watermarkImage = NULL, $marginRight = 5, $marginBottom = 5 )

	- save( $outputDir = NULL, $returnObject = FALSE )
	- rollback()
	- display()
	- getError()


/=====  End of EZimage Component For CakePHP  ======*/



/**
* 
*/
class EZimageComponent extends Component
{
	public $watermarkImage;
	public $uploadedFile = '';
	public $saved = array();
	public $quality = 100;

	private $error = '';
	private $allowedTypes;

	private $image;
	private $imgPath;
	private $imgData;

	public function initialize(Controller $controller )
	{
		$this->watermarkImage = APP.'webroot'.DS.'img'.DS.'watermark.png';
		$this->allowedTypes = array( IMAGETYPE_JPEG => 'jpeg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif' );
	}

	public function upload( $uploadData, $uploadDir, $filename = NULL, $returnObject = FALSE )
	{
		$result = !$returnObject ? FALSE : $this;
		# Periksa parameter
			if ( !is_array( $uploadData ) )// Upload data
			{
				$this->error = 'Invalid upload data';
				return $result;
			}

			if ( !is_dir( $uploadDir ) || !is_writable( $uploadDir ) )
			{
				$this->error = 'Destination path is not directory or not writable!';
				return $result;
			}

		# Pastikan fail yang dimuat-naik adalah imej JPG, PNG atau GIF
			$ext =  explode( '.', $uploadData[ 'name' ] );
			$ext = strtolower(end( $ext ));
			if ( !in_array( $ext, array( 'jpeg', 'jpg', 'png', 'gif' )) )
			{
				$this->error = 'The file must be an JPG, PNG or GIF image!';
				return $result;
			}

		# Muat-naik imej ke direktori yang ditetapkan
			$filename = !$filename ? $uploadData[ 'name' ] : $filename;
			$uploadPath = $uploadDir.DS.$filename;
			if ( !move_uploaded_file( $uploadData['tmp_name'], $uploadPath ) )
			{
				$this->error = 'Error while upload the image!';
				return $result;
			}

			$this->uploadedFile = $uploadPath;

		return !$returnObject ? $uploadPath : $this ;
	}

	public function file( $filepath = NULL )
	{
		# Periksa ralat
			if ( $this->error )
			{
				return $this;
			}

		# Periksa filepath
			$filepath = $filepath ? $filepath : $this->uploadedFile;
			if ( !$filepath )
			{
				$this->error = 'Undefined file path';
				return $this;
			}

		# Pengesahan
			if ( !is_file( $filepath ) )// Pastikan laluan fail adalah sah
			{
				$this->error = 'Invalid file';
				return $this;
			}

			$imgData = getimagesize( $filepath );
			$imgType = $imgData[2];
			$allowedTypes = $this->allowedTypes;
			if ( !array_key_exists( $imgType, $allowedTypes ) )// Pastikan hanya JPG, PNG dan GIF
			{
				$this->error = 'Hanya format imej JPG, PNG dan GIF dibenarkan';
				return $this;
			}

			if ( $imgType == IMAGETYPE_PNG )// Kemaskini property 'quality'
			{
				$quality = str_split( $this->quality );
				array_pop( $quality );
				$quality = intval( join( $quality ));
				$this->quality = $quality < 10 ? $quality : 9;
			}

		# Buat imej baru
			$imageCreate = 'imagecreatefrom'.$allowedTypes[ $imgType ];

		$this->image = $imageCreate( $filepath );
		$this->imgPath = $filepath;
		$this->imgData = $imgData;

		return $this;
	}

	public function crop( $width = 0, $height = 0, $x = 0, $y = 0 )
	{
		# Periksa ralat
			if ( $this->error )
			{
				return $this;
			}

		$imgDefault = $this->image;
		$newImage = imagecreatetruecolor( $width, $height );//canvas
		imagecopyresampled( $newImage, $imgDefault, 0, 0, $x, $y, $width, $height, $width, $height );

		# Simpan nilai baru
			$this->image = $newImage;
			$this->imgData[0] = $width;
			$this->imgData[1] = $height;
		//pr( $this );exit();
		return $this;
	}

	public function cropAtCenter( $width = 0, $height = 0 )
	{
		# Periksa ralat
			if ( $this->error )
			{
				return $this;
			}

		# Tetapkan lebar dan tinggi, jika belum ditetapkan
			list( $defaultWidth, $defaultHeight ) = $this->imgData;

			$isSquare = $defaultWidth == $defaultHeight;
			$isVertical = $defaultWidth > $defaultHeight;
			if ( !$width && !$height )// Parameter tidak ditetapkan
			{
				$width = $defaultWidth;
				if ( !$isSquare )// jika bukan segi empat sama
				{
					$width = $isVertical ? $defaultHeight : $defaultWidth;
				}
				$height = $width;
			}

		# Dapatkan kedudukan titik tengah imej (titik x dan y)
			$x = ($defaultWidth-$width)/2;
			$y = ($defaultHeight-$height)/2;
			
			return $this->crop( $width, $height, $x, $y );
	}

	public function resize( $width = 0, $height = 0, $proportional = TRUE )
	{
		# Periksa ralat
			if ( $this->error )
			{
				return $this;
			}

		# Periksa nilai parameter
			$isAuto = strtolower( $height ) === 'auto';
			if ( ( !$width && !$height ) ||
				 ( !$width && $isAuto ) ||
				 ( $isAuto && !$proportional ) )
			{
				$this->error = 'Invalid width or height value';
				return $this;
			}
			$height = 0;

		$imgDefault = $this->image;
		list( $defaultWidth, $defaultHeight ) = $this->imgData;


		
		# Menetapkan lebar dan tinggi mengikut kesesuaian
			if ( $proportional )
			{
				if ( $isAuto )
				{
					$height = 0;
					if ( $defaultWidth < $defaultHeight )
					{
						$height = $width;
						$width = 0;
					}
				}

				# Kecilkan kepada lebar dan tinggi asal(Jika melebihi nilai original)
					if ( $width > $defaultWidth || $height > $defaultHeight )
					{
						$width = $defaultWidth;
						$height = $defaultHeight;
					}
					else
					{
					# Ingin kecilkan atau besarkan?
						if ( $defaultWidth > $width )//kecilkan
						{
							if ( $width > $height )//kemaskini nilai 'height'
							{
								$height = $defaultHeight / ( $defaultWidth / $width );
							}
							else
							{
								$width = $defaultWidth / ( $defaultHeight / $height );
							}
						}
						else//besarkan
						{
							if ( $width > $height )//kemaskini nilai 'height'
							{
								$height = $defaultHeight * ( $width / $defaultWidth );
							}
							else
							{
								$width = $defaultWidth * ( $height / $defaultHeight );
							}
						}
					}
			}

		$newImage = imagecreatetruecolor( $width, $height );//canvas
		imagecopyresampled( $newImage, $imgDefault, 0, 0, 0, 0, $width, $height, $defaultWidth, $defaultHeight );
		
		# Simpan nilai baru
			$this->image = $newImage;
			$this->imgData[0] = $width;
			$this->imgData[1] = $height;
		
		return $this;
	}

	public function watermark( $watermarkImage = NULL, $marginRight = 5, $marginBottom = 5 )
	{
		# Periksa ralat
			if ( $this->error )
			{
				return $this;
			}

		$watermarkImage = !$watermarkImage ? $this->watermarkImage : $watermarkImage ;

		# watermark:
			$imgDefault = $this->image;
			$watermark = imagecreatefrompng( $watermarkImage );
			list( $watermark_width, $watermark_height ) = getimagesize( $watermarkImage );
			list( $width, $height ) = $this->imgData;

			// placing the watermark 5px from bottom and right
				$watermark_x = $width - $watermark_width - $marginRight;  
				$watermark_y = $height - $watermark_height - ($marginBottom-2);

			// blending the images together
				imagealphablending( $imgDefault, TRUE );
				imagealphablending( $watermark, TRUE );

			// creating the new image
				imagecopy( $imgDefault, $watermark, $watermark_x, $watermark_y, 0, 0, $watermark_width, $watermark_height );
				$this->image = $imgDefault;

		return $this;
	}

	public function save( $outputDir = NULL, $returnObject = FALSE )
	{
		$result = !$returnObject ? FALSE : $this;
		# Periksa ralat
			if ( $this->error )
			{
				return $result;
			}

		# Pastikan direktori output adalah 'writable'
			$outputDir = $outputDir ? $outputDir : dirname( $this->imgPath );
			if ( !is_writable( $outputDir ) )
			{
				$this->error = 'Output directory is not writeable!';
				return $result;
			}

		# Simpan imej yang telah diproses
			$imgType = $this->imgData[2];
			$imageFinish = 'image'.$this->allowedTypes[ $imgType ];
			$filepath = $outputDir ? $outputDir.DS.basename( $this->imgPath ): $this->imgPath ;
			if ( !$imageFinish( $this->image, $filepath, $this->quality ) )
			{
				$this->error = 'Failed to save image';
				return $result;
			}

			if ( $returnObject )
			{
				$this->saved[] = $filepath;
				return $this;
			}
		return TRUE;
	}
	
	public function rollback()
	{
		$result = TRUE;
		# Jika terdapat ralat
			if ( $this->error )
			{
			# Padam semula semua imej yang telahpun disimpan
				foreach ( $this->saved as $i => $path )
				{
					if ( file_exists( $path ) && unlink( $path ) )
					{
						unset( $this->saved[ $i ] );
					}
				}
				$result = FALSE;
			}
			clearstatcache();//buang kesemua cache function_exists, is_file, is_dir, is_writable
		return $result;
	}

	public function display()
	{
		# Periksa ralat
			if ( $this->error )
			{
				header( 'HTTP/1.0 404 Not Found' );
				exit();
			}

		$imgData = $this->imgData;

		// this tells the browser to render processed image
			header( 'content-type: '.$imgData[ 'mime' ] );

		$imgType = $imgData[2];
		$imageFinish = 'image'.$this->allowedTypes[ $imgType ];
		$imageFinish( $this->image, NULL, $this->quality );
		imagedestroy( $this->image );
	}

	public function getError()
	{
		return $this->error;
	}

	public function test()
	{
		$this->autoRender = FALSE;

		$imagePath = APP.'webroot'.DS.'photos'.DS.'test'.DS;
		$filename = 'default.jpg';
		$filepath = $imagePath.'from'.DS. $filename;
		$saveTo = $imagePath.'to'.DS;

		# Senarai parameter untuk crop, resize, watermark
			# file:
				$filepath = $filepath;
			# crop:
				//$new_width = 400;
				//$new_height = 100;
				//$new_x = 0;
				//$new_y = 100;
			# resize:
				//$new_width = 400;
				//$new_height = 100;

		# Senarai var
			$imagePath = APP.'webroot'.DS.'photos'.DS.'test'.DS;
			//$watermarkImage = APP.'webroot'.DS.'img'.DS.'cake.icon.png';
			$watermarkImage = APP.'webroot'.DS.'img'.DS.'cake.power.gif';
			$jpegQuality = 100;
			
		# file:
			list( $width, $height ) = getimagesize( $filepath );
			$imgDefault = imagecreatefromjpeg( $filepath );

		# crop:
			//$newImage = imagecreatetruecolor( $new_width, $new_height );//canvas
			//imagecopyresampled( $newImage, $imgDefault, 0, 0, $new_x, $new_y, $new_width, $new_height, $new_width, $new_height );

		# resize:
			//$newImage = imagecreatetruecolor( $new_width, $new_height );//canvas
			//imagecopyresampled( $newImage, $imgDefault, 0, 0, 0, 0, $new_width, $new_height, $width, $height );

		# watermark:
			$watermark = imagecreatefromgif( $watermarkImage );
			list( $watermark_width, $watermark_height ) = getimagesize( $watermarkImage );

			// placing the watermark 5px from bottom and right
				$watermark_x = $width - $watermark_width - 5;  
				$watermark_y = $height - $watermark_height - 5;

			// blending the images together
				imagealphablending( $imgDefault, TRUE );
				imagealphablending( $watermark, TRUE );

			// creating the new image
				imagecopy( $imgDefault, $watermark, $watermark_x, $watermark_y, 0, 0, $watermark_width, $watermark_height );
				$newImage = $imgDefault;
		# save:
			imagejpeg( $newImage, $saveTo.$filename, $jpegQuality );
	}


}




?>