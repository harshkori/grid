<?php
namespace Customodule\Topmenu\Controller\Adminhtml\Allmenu;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem;
// use Customodule\Extension\Model\ExtensionFactory;
use Customodule\Topmenu\Model\ViewFactory;
use Customodule\Topmenu\Model\View;

use Magento\Framework\Setup\SchemaSetupInterface;


class Import extends \Magento\Framework\App\Action\Action

{   
    protected $messageManager;
    protected $filesystem;
    protected $collectionFactory;

    protected $dir;

    protected $_pageFactory;
    protected $fileUploader;

    public function __construct
    (
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        ManagerInterface $messageManager,
        Filesystem $filesystem,
        ResourceConnection $resourceConnection,
        UploaderFactory $fileUploader,
        ViewFactory $collectionFactory,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        SchemaSetupInterface $setup,
        View $collection

    )
    {   
        $this->resourceConnection   =       $resourceConnection;
        $this->messageManager       =       $messageManager;
        $this->filesystem           =       $filesystem;
        $this->fileUploader         =       $fileUploader;
        $this->mediaDirectory       =       $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $this->_pageFactory         =       $pageFactory;
        $this->collectionFactory    =       $collectionFactory;
        $this->setup                =       $setup->startSetup();
        $this->collection           =       $collection;

        return parent::__construct($context);
    }

    public function execute()
    {
        
        $resultRender = $this->_pageFactory->create();
        $redirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $redirect->setUrl('pro/allmenu/import/');
        $resultRender->getConfig()->getTitle()->prepend(__('Product Import'));
        $collection=$this->collectionFactory->create();
        $newcollection=$collection->getCollection();
        $data=$newcollection->getData();
        // echo "<pre>";
        $col=[];
        foreach($data as $key => $value)
        {
          $col[]= $value['name'];
           
        }
        // print_r($col);
        // echo "<pre>";
        // // print_r($newcollection->getData());
        // exit();
        // $bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
        // $objectManager = $bootstrap->getObjectManager();

        if($_POST)
        {
            

            if($_FILES['csvfiles']['name'])
            {
                // $collection=$this->collection->getData();
                // print_r($collection);
                // exit();
                
                $target = $this->mediaDirectory->getAbsolutePath('media');
                $uploader = $this->fileUploader->create(['fileId' => 'csvfiles']);
                $uploader->setFilesDispersion(false);
                $uploader = $this->fileUploader->create(['fileId' =>'csvfiles']);
                $uploader->setAllowedExtensions(['jpg', 'pdf', 'csv', 'png', 'zip']);
                $uploader->setAllowCreateFolders(true);
                $uploader->setAllowRenameFiles(true);                                      

                $filename = explode(".", $_FILES['csvfiles']['name']);//explode for convert string to array
            
                if(end($filename) == "csv")
                {   
                    $handle = fopen($_FILES['csvfiles']['tmp_name'], "r");
                    while($data = fgetcsv($handle))
                    {   

                        if($data[0] == 'product_name' ||$data[1] == 'product_category' || $data[2] == 'product_price' ||$data[3] == 'sku')
                        {
                            echo "";
                        }

                        else
                        {
                            
                            if(in_array($data[0],$col) or $data[0]=="")
                            {
                                //out from entire loop

                                // $this->messageManager->addError(__('Some Item is All Ready Exists In Databse.'));

                            }
                            else
                            {
                            $event=$this->collectionFactory->create();

                            $event->setData('name',$data[0]);
                            $event->setData('product_type',$data[1]);
                            $event->setData('special_price',$data[2]);
                            $event->setData('sku',$data[3]);
                            $event->setData('categories',$data[4]);
                            $event->setData('product_websites',$data[5]);   
                            $event->setData('description',$data[6]);   
                            $event->setData('short_description',$data[7]);   
                            $event->setData('weight',$data[8]);
                            $event->setData('product_online',$data[9]);   
                            $event->setData('tax_class_name',$data[10]);   
                            $event->setData('visibility',$data[11]);
                            $event->setData('qty',$data[12]);
                            $event->save(); 
                            // $result = $uploader->save($target);    
                            $this->messageManager->addSuccess(__('Successfully saved the item.'));
                            $bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
                            $objectManager = $bootstrap->getObjectManager();
                            $state = $objectManager->get('Magento\Framework\App\State');
                            $state->setAreaCode('adminhtml');
                        
                            
                            $product = $objectManager->create('Magento\Catalog\Model\Product');                                    
                            $product->setTypeId('simple') // product type
                            ->setStatus(1) // 1 = enabled
                            ->setAttributeSetId(4)
                            ->setName($data[0])
                            ->setSku($data[3])
                            ->setPrice($data[2])
                            ->setTaxClassId(0) // 0 = None
                            ->setCategoryIds(array(2, 3)) // array of category IDs, 2 = Default Category
                            ->setDescription($data[6])
                            ->setShortDescription($data[7])
                            ->setWebsiteIds(array(1)) // Default Website ID
                            ->setStoreId(0) // Default store ID
                            ->setVisibility(4) // 4 = Catalog & Search
                            ->save();
        
        
                            }
                        
                        }
                    }
                    fclose($handle);
                    return $redirect;


                }   
        
            }
        }
        // else
        // {
        //     $this->messageManager->addWarning(__('Something Went Wrong'));

        // }
        return $resultRender;
    }  
}  