## EZimage - Image Component For CakePHP

Easy image manipulation component for CakePHP just like QImage... It is replacement... It can crop, resize, watermark and save or display the processed image directly to the browser... 

### How To Install?

1. Download the component by click to this link [*Download EZimageComponent*](https://github.com/elmyrockers/EZimage/archive/master.zip).
2. Extract the downloaded file using your compression tool like 7Zip,Winrar or Winzip.
3. Copy 'app' folder then put that to the root of your project folder.
	Example: /your_project/app/Controller/Component/EZimageComponent.php
4. Then inside your controller file, add 'EZimage' to the $components property just like below:

```
<?php
class ProjectController extends AppController
{
	public $components = array( 'EZimage' );
}
?>
```

### How To Use This Component?

```
	$this->EZimage->upload( $uploadData, $uploadDir, $filename = NULL, $returnObject = FALSE );
	$this->EZimage->file( $filepath )->crop( $width, $height, $x, $y )->save( $outputDir, $returnObject );
	$this->EZimage->file( $filepath )
				  ->cropAtCenter( $width, $height, $x, $y )
				  ->resize( $width, $height, $proportional )
				  ->save( $outputDir, $returnObject );
```

### Reference

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


### Support or Contact

Having trouble with Pages? Check out our [documentation](https://help.github.com/categories/github-pages-basics/) or [contact support](https://github.com/contact) and we’ll help you sort it out.
