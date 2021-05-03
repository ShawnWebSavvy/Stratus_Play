<?php

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\I18n\I18n;
use Cake\Controller\Component\AuthComponent;
use Cake\Controller\Component\UrlfriendlyComponent;
use Cake\Controller\Component\FileUploadComponent;
use Cake\Controller\Component\CurrencyComponent;
use Cake\Utility\Hash;
use Cake\Datasource\ConnectionManager;

class ApiController extends AppController
{

	public $names = 'Api';

	public function beforeFilter(Event $event)
	{
		parent::beforeFilter($event);

		$this->loadModel('Managemodules');
		$languagetable = TableRegistry::get('Languages');
		$languages = $languagetable->find()->all()->toArray();

		$managemoduleModel = $this->Managemodules->find()->first();
		if ($managemoduleModel->site_maintenance_mode == 'yes') {
			echo '{"status":"error","message":"Site under maintenance mode"}';
			die;
		}
			//parent::beforeFilter();
		if ($_POST['user_id']) {
			$userId = $_POST['user_id'];
			$this->loadModel('Users');
			$user = $this->Users->find()->where(['id' => $userId])->first();//andWhere(['user_status'=>'enable'])->first();
			if (count($user) == 0) {
				echo '{"status":"error","message":"Your account is deleted by Admin"}';
				die;
			} elseif ($user['user_status'] == "disable") {
				echo '{"status":"error","message":"The user has been blocked by admin"}';
				die;
			}

	        	/* if($user['languagecode']!=""){
            		I18n::locale($user['languagecode']);
            	}*/

            }

        }

        function home()
        {
        	$user_id = $_POST['user_id'];
        	$tdy = strtotime(date("Y-m-d"));
        	

        	$banner_detail = $this->banner();
        	
        	$category_detail = $this->category();
        	$daily_deals = $this->dailyDeals();

        	$this->loadModel('Homepagesettings');
			$homepageModel = $this->Homepagesettings->find()->where(['layout' => 'custom'])->first();
			//$widgets = $homepageModel->widgets;
			$widgets = explode('(,)',$homepageModel['widgets']);
			$widget_settings = $homepageModel->widget_settings;
			$widget_settings = (array) json_decode($widget_settings);

			$resultArrayss = array();
			foreach ($widgets as $widget){
			switch ($widget){
				case 'Recently Added':
					$resultArrayss[] = 'recently_added';
					break;
				case 'Most Popular':
					$resultArrayss[] = 'most_popular';
					break;
				case 'Top Stores':
					$resultArrayss[] = 'popular_stores';
					break;

				case 'Top Rated':
					$resultArrayss[] = 'top_rated';
					break;

				case 'Categories':
					$resultArrayss[] = 'categories';
					break;

				case 'Discounts':
					$resultArrayss[] = 'discounts';
					break;

				case 'Suggested Items':
					$resultArrayss[] = 'suggested_items';
					break;
				
				case 'Featured Items':
					$resultArrayss[] = 'featured_items';
					break;
			}
		}

		

		$re1array = array();
		$re1array[$resultArrayss[0]] = (!isset($widget_settings[$resultArrayss[0]])) ? '' : $widget_settings[$resultArrayss[0]] ;
		$re1array[$resultArrayss[1]] = (!isset($widget_settings[$resultArrayss[1]])) ? '' : $widget_settings[$resultArrayss[1]] ;
		$re1array[$resultArrayss[2]] = (!isset($widget_settings[$resultArrayss[2]])) ? '' : $widget_settings[$resultArrayss[2]];



		
		$data = array();

		$data[$resultArrayss[0]] = $this->getData($resultArrayss[0],$user_id, $homepageModel->categories, $widget_settings[$resultArrayss[0]]);
		$data[$resultArrayss[1]] = $this->getData($resultArrayss[1],$user_id, $homepageModel->categories,$widget_settings[$resultArrayss[1]]);
		$data[$resultArrayss[2]] = $this->getData($resultArrayss[2],$user_id, $homepageModel->categories,$widget_settings[$resultArrayss[2]]);



		$order = array();
		$order['one'] = $resultArrayss[0];
		$order['two'] = $resultArrayss[1];
		$order['three'] = $resultArrayss[2];
		
		$orders = json_encode($order);

		echo '{"status":"true","ascending_order":'.$orders.',"layout_design": '.json_encode($re1array).',"banner": ' . $banner_detail . ',"category": ' . $category_detail . ',"daily_deals": {"valid_till": ' . $tdy . ', "items":' . $daily_deals . ', "valid_time":' . time() . '},"item_lists":' . json_encode($data).'}';
        	die;
        }

        function getData($type=null, $user_id=null, $categories=null, $layout=null){


        	//$popular_products = $this->popularProducts();
        	//echo $type; die;
        	$data = array();
        	if($type == 'most_popular'){
        		$data =  $this->popularProducts(0, 10, $layout);
			}elseif($type == 'discounts'){
				$data =  $this->discountProducts(0, 10, $layout);
			}elseif($type == 'featured_items'){
				//echo 'layout'.$layout; die;
				$data =  $this->featuredProducts(0, 10, $layout);
			}elseif($type == 'categories'){
				$data =  $this->categoryProducts($categories, 0, 10, $layout, $user_id);
			}elseif($type == 'top_rated'){
				$data =  $this->topRatedproducts(0, 10, $layout);
			}elseif($type == 'recently_added'){
				$data = $this->recentProducts(0, 10, $layout);
			}elseif($type == 'popular_stores'){
				$data = $this->popularStores();
			}elseif($type == 'suggested_items'){
				$data = $this->suggestedItems($user_id);
			}

			//echo '<pre>'; print_r($data); die;
			return $data;
        }


        function homesection2()
        {
        	$user_id = $_POST['user_id'];
        	$tdy = strtotime(date("Y-m-d"));

        
        	//$banner_detail = $this->banner();
        	//$category_detail = $this->category();
        	// /$daily_deals = $this->dailyDeals();

        	$this->loadModel('Homepagesettings');
			$homepageModel = $this->Homepagesettings->find()->where(['layout' => 'custom'])->first();


			//$widgets = $homepageModel->widgets;
			$widgets = explode('(,)',$homepageModel->widgets);
			$widget_settings = $homepageModel->widget_settings;
			$widget_settings = (array) json_decode($widget_settings);


			$resultArrayss = array();
			foreach ($widgets as $widget){
			switch ($widget){
				case 'Recently Added':
					$resultArrayss[] = 'recently_added';
					break;
				case 'Most Popular':
					$resultArrayss[] = 'most_popular';
					break;
				case 'Top Stores':
					$resultArrayss[] = 'popular_stores';
					break;

				case 'Top Rated':
					$resultArrayss[] = 'top_rated';
					break;

				case 'Categories':
					$resultArrayss[] = 'categories';
					break;

				case 'Discounts':
					$resultArrayss[] = 'discounts';
					break;

				case 'Suggested Items':
					$resultArrayss[] = 'suggested_items';
					break;
				
				case 'Featured Items':
					$resultArrayss[] = 'featured_items';
					break;
			}
		}

		

		$re1array = array();
		$re1array[$resultArrayss[3]] = (!isset($widget_settings[$resultArrayss[3]])) ? '' : $widget_settings[$resultArrayss[3]] ;
		$re1array[$resultArrayss[4]] = (!isset($widget_settings[$resultArrayss[4]])) ? '' : $widget_settings[$resultArrayss[4]];
		$re1array[$resultArrayss[5]] = (!isset($widget_settings[$resultArrayss[5]])) ? '' : $widget_settings[$resultArrayss[5]];
		
		$data = array();
		/*
		$data[$resultArrayss[3]] = (empty($getArraylist[$resultArrayss[3]])) ? array() : $getArraylist[$resultArrayss[3]];
		$data[$resultArrayss[4]] = (empty($getArraylist[$resultArrayss[4]])) ? array() : $getArraylist[$resultArrayss[4]];
		$data[$resultArrayss[5]] = (empty($getArraylist[$resultArrayss[5]])) ? array() : $getArraylist[$resultArrayss[5]];
		*/

		//print_r($resultArrayss);die;

		$data[$resultArrayss[3]] = $this->getData($resultArrayss[3],$user_id, $homepageModel->categories, $widget_settings[$resultArrayss[3]]);
		$data[$resultArrayss[4]] = $this->getData($resultArrayss[4],$user_id, $homepageModel->categories, $widget_settings[$resultArrayss[4]]);
		


		$data[$resultArrayss[5]] = $this->getData($resultArrayss[5],$user_id, $homepageModel->categories,$widget_settings[$resultArrayss[5]]);

		//print_r($data); die;
		$order = array();
		$order['one'] = (!isset($resultArrayss[3])) ? '' : $resultArrayss[3] ;
		$order['two'] = (!isset($resultArrayss[4])) ? '' : $resultArrayss[4] ;
		$order['three'] = (!isset($resultArrayss[5])) ? '' : $resultArrayss[5] ;
		
		$orders = json_encode($order);

		echo '{"status":"true","ascending_order":'.$orders.',"layout_design": '.json_encode($re1array).', "item_lists":' . json_encode($data).'}';
        	die;
        }


        function homesection3()
        {
        	$user_id = $_POST['user_id'];
        	$tdy = strtotime(date("Y-m-d"));

        	//$banner_detail = $this->banner();
        	//$category_detail = $this->category();
        	//$daily_deals = $this->dailyDeals();

        	$this->loadModel('Homepagesettings');
			$homepageModel = $this->Homepagesettings->find()->where(['layout' => 'custom'])->first();
			//$widgets = $homepageModel->widgets;
			$widgets = explode('(,)',$homepageModel['widgets']);
			$widget_settings = $homepageModel->widget_settings;
			$widget_settings = (array) json_decode($widget_settings);
			
			$popular_products = $this->popularProducts();
        	$recent_products = $this->recentProducts();
        	$featured_products = $this->featuredProducts();
        	$popular_stores = $this->popularStores();
        	//$category = $this->category();
        	$discountProducts = $this->discountProducts();
        	$categoryProducts = $this->categoryProducts($homepageModel->categories);
        	$topRated = $this->topRatedproducts();
        	$suggestItms = $this->suggestedItems($user_id);

			$getArraylist = array(
				'most_popular'=>$popularProducts,
				'discounts'=>$discountProducts,
				'featured_items'=>$featured_products,
				'categories'=>$categoryProducts,
				'top_rated'=>$topRated,
				'recently_added'=>$recent_products,
				'popular_stores'=>$popular_stores,
				'suggested_items'=>$suggestItms
				);


			$resultArrayss = array();
			foreach ($widgets as $widget){
			switch ($widget){
				case 'Recently Added':
					$resultArrayss[] = 'recently_added';
					break;
				case 'Most Popular':
					$resultArrayss[] = 'most_popular';
					break;
				case 'Top Stores':
					$resultArrayss[] = 'popular_stores';
					break;

				case 'Top Rated':
					$resultArrayss[] = 'top_rated';
					break;

				case 'Categories':
					$resultArrayss[] = 'categories';
					break;

				case 'Discounts':
					$resultArrayss[] = 'discounts';
					break;

				case 'Suggested Items':
					$resultArrayss[] = 'suggested_items';
					break;
				
				case 'Featured Items':
					$resultArrayss[] = 'featured_items';
					break;
			}
		}

		if(!isset($resultArrayss[6]) )
		{
			//echo '{"status":"true","message":"Empty"}';
			//die;
		}

		$re1array = array();
		$re1array[$resultArrayss[6]] = (!isset($widget_settings[$resultArrayss[6]])) ? '' : $widget_settings[$resultArrayss[6]] ;
		$re1array[$resultArrayss[7]] = (!isset($widget_settings[$resultArrayss[7]])) ? '' : $widget_settings[$resultArrayss[7]];
		//$re1array[$resultArrayss[5]] = $widget_settings[$resultArrayss[5]];
		
		$data = array();
		/*
		$data[$resultArrayss[3]] = $this->getData($resultArrayss[3],$user_id, $homepageModel->categories);
		$data[$resultArrayss[4]] = $this->getData($resultArrayss[4],$user_id, $homepageModel->categories);
		*/

		$data[$resultArrayss[6]] = $this->getData($resultArrayss[6],$user_id, $homepageModel->categories);
		$data[$resultArrayss[7]] = $this->getData($resultArrayss[7],$user_id, $homepageModel->categories);
		//$data[$resultArrayss[5]] = $getArraylist[$resultArrayss[5]];

		//echo '<pre>'; print_r($data); die;
		$order = array();
		$order['one'] = (!isset($resultArrayss[6])) ? '' : $resultArrayss[6] ;
		$order['two'] = (!isset($resultArrayss[7])) ? '' : $resultArrayss[7] ;
		
		$orders = json_encode($order);

		echo '{"status":"true","ascending_order":'.$orders.',"layout_design": '.json_encode($re1array).',"daily_deals": {"valid_till": ' . $tdy . ', "valid_time":' . time() . '},"item_lists":' . json_encode($data).'}';
        	die;
        }


        function homsse()
        {
        	$user_id = $_POST['user_id'];
        	$tdy = strtotime(date("Y-m-d"));

        	$banner_detail = $this->banner();
        	$category_detail = $this->category();
        	$daily_deals = $this->dailyDeals();

        	$this->loadModel('Homepagesettings');
			$homepageModel = $this->Homepagesettings->find()->where(['layout' => 'custom'])->first();
			//$widgets = $homepageModel->widgets;
			$widgets = explode('(,)',$homepageModel['widgets']);
			$widget_settings = $homepageModel->widget_settings;
			$widget_settings = (array) json_decode($widget_settings);
			
			$popular_products = $this->popularProducts();
        	$recent_products = $this->recentProducts();
        	$featured_products = $this->featuredProducts();
        	$popular_stores = $this->popularStores();
        	//$category = $this->category();
        	$discountProducts = $this->discountProducts();
        	$categoryProducts = $this->categoryProducts();
        	$topRated = $this->topRatedproducts();
        	$suggestItms = $this->suggestedItems();

			$getArraylist = array(
				'most_popular'=>$popularProducts,
				'discounts'=>$discountProducts,
				'featured_items'=>$featured_products,
				'categories'=>$categoryProducts,
				'top_rated'=>$topRated,
				'recently_added'=>$recent_products,
				'popular_stores'=>$popular_stores,
				'suggested_items'=>$suggestItms
				);

			$resultArrayss = array();
			foreach ($widgets as $widget){
			switch ($widget){
				case 'Recently Added':
					$resultArrayss[] = 'recently_added';
					break;
				case 'Most Popular':
					$resultArrayss[] = 'most_popular';
					break;
				case 'Top Stores':
					$resultArrayss[] = 'popular_stores';
					break;

				case 'Top Rated':
					$resultArrayss[] = 'top_rated';
					break;

				case 'Categories':
					$resultArrayss[] = 'categories';
					break;

				case 'Discounts':
					$resultArrayss[] = 'discounts';
					break;

				case 'Suggested Items':
					$resultArrayss[] = 'suggested_items';
					break;
				
				case 'Featured Items':
					$resultArrayss[] = 'featured_items';
					break;
			}
		}

		$re1array = array();
		$re1array[$resultArrayss[0]] = $widget_settings[$resultArrayss[0]];
		$re1array[$resultArrayss[1]] = $widget_settings[$resultArrayss[1]];
		$re1array[$resultArrayss[2]] = $widget_settings[$resultArrayss[2]];
		
		$data = array();
		$data[$resultArrayss[0]] = $getArraylist[$resultArrayss[0]];
		$data[$resultArrayss[1]] = $getArraylist[$resultArrayss[1]];
		$data[$resultArrayss[2]] = $getArraylist[$resultArrayss[2]];

		//echo '<pre>'; print_r($data); die;

		echo '{"status":"true","layout_design": '.json_encode($re1array).',"banner": ' . $banner_detail . ',"category": ' . $category_detail . ',"daily_deals": {"valid_till": ' . $tdy . ', "items":' . $daily_deals . ', "valid_time":' . time() . '},"item_lists":' . json_encode($data).'}';
        	die;
        }

        function homeapinew()
        {
        	$user_id = $_POST['user_id'];
        	$tdy = strtotime(date("Y-m-d"));

        	$banner_detail = $this->banner();
        	$category_detail = $this->category();
        	$daily_deals = $this->dailyDeals();
        	$popular_products = $this->popularProducts();
        	$recent_products = $this->recentProducts();
        	$featured_products = $this->featuredProducts();
        	$popular_stores = $this->popularStores();
        	$category = $this->category();
        	echo '{"status":"true","banner": ' . $banner_detail . ',"category": ' . $category_detail . ',"daily_deals": {"valid_till": ' . $tdy . ', "items":' . $daily_deals . ', "valid_time":' . time() . '},"popular_products":' . $popular_products . ',"recent_products":' . $recent_products . ',"featured_products":' . $featured_products . ',"popular_stores":' . $popular_stores . '}';
        	die;
        }

        function homeabbs()
        {
        	$user_id = $_POST['user_id'];
        	$tdy = strtotime(date("Y-m-d"));

        	$this->loadModel('Homepagesettings');
			$homepageModel = $this->Homepagesettings->find()->where(['layout' => 'custom'])->first();

			$getwidget_settings = json_decode($homepageModel->widget_settings);

			$getwidget_settings = (array)$getwidget_settings;

			
			$layout1 = array_search('slider1',$getwidget_settings);
			$layout2 = array_search("slider2",$getwidget_settings);
			$layout3 = array_search("slider3",$getwidget_settings);

			$layout1 = str_replace("'", "", $layout1);
			$layout2 = str_replace("'", "", $layout2);
			$layout3 = str_replace("'", "", $layout3);

			
        	$banner_detail = $this->banner();
        	$category_detail = $this->category();
        	$daily_deals = $this->dailyDeals();

        	$popular_products = $this->popularProducts();
        	$recent_products = $this->recentProducts();
        	$featured_products = $this->featuredProducts();
        	$popular_stores = $this->popularStores();
        	//$category = $this->category();
        	$discountProducts = $this->discountProducts();
        	$categoryProducts = $this->categoryProducts();
        	$topRated = $this->topRatedproducts();
        	$suggestItms = $this->suggestedItems();

        	$getArraylist = array(
				'most_popular'=>$popularProducts,
				'discounts'=>$discountProducts,
				'featured_items'=>$featured_products,
				'categories'=>$categoryProducts,
				'top_rated'=>$topRated,
				'recently_added'=>$recent_products,
				'popular_stores'=>$popular_stores,
				'suggested_items'=>$suggestItms
				);

        	$data1 = (empty($getArraylist[$layout1])) ? '""' : $getArraylist[$layout1];
        	$data2 = (empty($getArraylist[$layout2])) ? '""' : $getArraylist[$layout2];
        	$data3 = (empty($getArraylist[$layout3])) ? '""' : $getArraylist[$layout3];

        	
        	echo '{"status":"true","banner": ' . $banner_detail . ',"category": ' . $category_detail . ',"daily_deals": {"valid_till": ' . $tdy . ', "items":' . $daily_deals . ', "valid_time":' . time() . '},
        	"'.$layout1.'":' . $data1 . ',"'.$layout2.'":' . $data2 . ',"'.$layout3.'":' . $data3 . '}';
        	
        	
        }


        function homesecdddtion2()
        {
        	$user_id = $_POST['user_id'];
        	$tdy = strtotime(date("Y-m-d"));

        	$this->loadModel('Homepagesettings');
			$homepageModel = $this->Homepagesettings->find()->where(['layout' => 'custom'])->first();

			$getwidget_settings = json_decode($homepageModel->widget_settings);

			$getwidget_settings = (array)$getwidget_settings;

			
			$layout1 = array_search('slider4',$getwidget_settings);
			$layout2 = array_search("slider5",$getwidget_settings);
			$layout3 = array_search("slider6",$getwidget_settings);

			$layout1 = str_replace("'", "", $layout1);
			$layout2 = str_replace("'", "", $layout2);
			$layout3 = str_replace("'", "", $layout3);

			
        	$banner_detail = $this->banner();
        	$category_detail = $this->category();
        	$daily_deals = $this->dailyDeals();

        	$popular_products = $this->popularProducts();
        	$recent_products = $this->recentProducts();
        	$featured_products = $this->featuredProducts();
        	$popular_stores = $this->popularStores();
        	//$category = $this->category();
        	$discountProducts = $this->discountProducts();
        	$categoryProducts = $this->categoryProducts();
        	$topRated = $this->topRatedproducts();
        	$suggestItms = $this->suggestedItems();

        	$getArraylist = array(
				'most_popular'=>$popularProducts,
				'discounts'=>$discountProducts,
				'featured_items'=>$featured_products,
				'categories'=>$categoryProducts,
				'top_rated'=>$topRated,
				'recently_added'=>$recent_products,
				'popular_stores'=>$popular_stores,
				'suggested_items'=>$suggestItms
				);

        	$data1 = (empty($getArraylist[$layout1])) ? '""' : $getArraylist[$layout1];
        	$data2 = (empty($getArraylist[$layout2])) ? '""' : $getArraylist[$layout2];
        	$data3 = (empty($getArraylist[$layout3])) ? '""' : $getArraylist[$layout3];

        	
        	echo '{"status":"true","banner": ' . $banner_detail . ',"category": ' . $category_detail . ',"daily_deals": {"valid_till": ' . $tdy . ', "items":' . $daily_deals . ', "valid_time":' . time() . '},
        	"'.$layout1.'":' . $data1 . ',"'.$layout2.'":' . $data2 . ',"'.$layout3.'":' . $data3 . '}';
        	
        	
        }


        function homesecddtion3()
        {
        	$user_id = $_POST['user_id'];
        	$tdy = strtotime(date("Y-m-d"));

        	$this->loadModel('Homepagesettings');
			$homepageModel = $this->Homepagesettings->find()->where(['layout' => 'custom'])->first();

			$getwidget_settings = json_decode($homepageModel->widget_settings);

			$getwidget_settings = (array)$getwidget_settings;

			
			$layout1 = array_search('slider7',$getwidget_settings);
			$layout2 = array_search("slider8",$getwidget_settings);
			

			$layout1 = str_replace("'", "", $layout1);
			$layout2 = str_replace("'", "", $layout2);
			

			
        	$banner_detail = $this->banner();
        	$category_detail = $this->category();
        	$daily_deals = $this->dailyDeals();

        	$popular_products = $this->popularProducts();
        	$recent_products = $this->recentProducts();
        	$featured_products = $this->featuredProducts();
        	$popular_stores = $this->popularStores();
        	//$category = $this->category();
        	$discountProducts = $this->discountProducts();
        	$categoryProducts = $this->categoryProducts();
        	$topRated = $this->topRatedproducts();
        	$suggestItms = $this->suggestedItems();

        	$getArraylist = array(
				'most_popular'=>$popularProducts,
				'discounts'=>$discountProducts,
				'featured_items'=>$featured_products,
				'categories'=>$categoryProducts,
				'top_rated'=>$topRated,
				'recently_added'=>$recent_products,
				'popular_stores'=>$popular_stores,
				'suggested_items'=>$suggestItms
				);


        	$data1 = (empty($getArraylist[$layout1])) ? '""' : $getArraylist[$layout1];
        	$data2 = (empty($getArraylist[$layout2])) ? '""' : $getArraylist[$layout2];
        	//$data3 = (empty($getArraylist[$layout3])) ? '' : $getArraylist[$layout3];

        	
        	echo '{"status":"true","banner": ' . $banner_detail . ',"category": ' . $category_detail . ',"daily_deals": {"valid_till": ' . $tdy . ', "items":' . $daily_deals . ', "valid_time":' . time() . '},
        	"'.$layout1.'":' . $data1 . ',"'.$layout2.'":' . $data2 . '}';
        }
        
        function discountProducts($offset = null, $limit = null, $layout = null)
        {
        	$items_data = array();
        	$this->loadModel('Items');
        	$itemstable = TableRegistry::get('Items');
        	if(isset($layout) && $layout == 'slider2')
        	{
        		$offset = 0;
				$limit = 20;
        	}elseif(isset($offset)) {
        		$offset = $offset;
        		$limit = $limit;
			}else{
				$offset = 0;
        		$limit = 10;
        	}

			$dataSourceObject = ConnectionManager::get('default');
	    	$getDiscounts = $dataSourceObject->execute("SELECT `id`,`user_id`,`dailydeal`,`discount_type`,`dealdate` FROM fc_items WHERE status='publish' AND discount_type = 'regular' ORDER BY id DESC LIMIT ".$limit." OFFSET ".$offset."")->fetchAll('assoc');

	    	//echo '<pre>'; print_r($getDiscounts); die;
			
			$d = 0;
			foreach($getDiscounts as $key=>$valspro){

				if($eachval['discount_type'] == 'daily' && $eachval['dealdate'] >= date("m/d/Y",time()))
	    			continue;

				$getitems = $itemstable->find()->contain('Photos')->contain('Forexrates')->contain('Users')->contain('Shops')->where(['Items.status'=>'publish','Items.id'=>$valspro['id']])->where(['Items.affiliate_commission IS NULL'])->first();

				$set = json_decode(json_encode($getitems));
				$getitems = (array)$set;
				$items_data[$d] = $getitems;

				$d++;
			}

			$resultArray = $this->convertJsonHomenew($items_data, $favitems_ids, $_POST['user_id']);
			return $resultArray;
        }

        function categoryProducts($category, $offset = null, $limit = null, $layout=null, $user_id=null)
        {	
        	//echo $user_id; die;
        	
        	if ($layout == 'slider2') {
				$offset = 0;
				$limit = 20;
			} elseif(isset($offset)) {
				$limit = $limit;
				$offset = $offset;
			}else{
				$limit = 20;
				$offset = 0;
			}


        	$itemstable = TableRegistry::get('Items');
        	$items_data = $itemstable->find()->contain('Photos')->contain('Forexrates')->contain('Users')->contain('Shops')->where(['Items.status'=>'publish','Items.category_id'=>$category])->where(['Items.affiliate_commission IS NULL'])->offset($offset)->limit($limit)->order(['Items.id DESC'])->all();
        	$resultArray = $this->convertJsonHome($items_data, '', $user_id);
        	return $resultArray;
        }

        function topRatedproducts($offset= null, $limit=null, $layout=null)
        {
        	$this->loadModel('Itemreviews');
        	$itemstable = TableRegistry::get('Items');
			$itemreviewTable = TableRegistry::get('Itemreviews');



			if ($layout == 'slider2') {
				$offset = 0;
				$limit = 20;
			} elseif(isset($offset)) {
				$limit = $limit;
				$offset = $offset;
			}else{
				$limit = 20;
				$offset = 0;
			}

			/*
			$results = $this->Itemreviews->find('all',
			    array('fields'=>array('DISTINCT Itemreviews.itemid','itemid','id'), 
			          'order'=>array('Itemreviews.ratings DESC'),
			          'limit' => $limit,
					'offset' => $offset,
			          )
			)->toArray();
			*/
			$results = $this->Items->find('all', array(
				'conditions' => array(
					'Items.status' => 'publish',
					'Items.avg_rating !=' => ''
				),
				'order' => 'avg_rating DESC',
				'limit' => $limit,
				'offset' => $offset,
			))->contain('Forexrates')->where(['Items.affiliate_commission IS NULL']);

			
			$favitems_ids = array();
			$resultArray = $this->convertJsonHome($results, $favitems_ids, $_POST['user_id']);
        	return $resultArray;
        }

        
        


        function getComments()
        {

        $this->loadModel('Comments');
        $this->loadModel('Users');
		$itemId = $_POST['item_id'];//$_POST['itemId'];
		$setngs = $this->Sitesettings->find()->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}
		if (isset($_POST['limit'])) {
			$limit = $_POST['limit'];
		} else {
			$limit = 10;
		}
		if (isset($_POST['offset'])) {
			$Details = $this->Comments->find('all', array(
				'conditions' => array(
					'item_id' => $itemId
				),
				'limit' => $limit,
				'offset' => $_POST['offset'],
				'order' => 'id DESC',
			));
		} else {
			$Details = $this->Comments->find('all', array(
				'conditions' => array(
					'item_id' => $itemId
				),
				'limit' => $limit,
				'order' => 'id DESC',
			));

		}

		foreach ($Details as $key => $details) {
			$resultArray[$key]['comment_id'] = $details['id'];
			$resultArray[$key]['comment'] = $details['comments'];
			$resultArray[$key]['user_id'] = $details['user_id'];
			$user_detail = $this->Users->find()->where(['id' => $details['user_id']])->first();
			$profileimage = $user_detail['profile_image'];
			if ($profileimage == "") {
				$profileimage = "usrimg.jpg";
			}
			$resultArray[$key]['user_image'] = $img_path . 'media/avatars/thumb150/' . $profileimage;
			$resultArray[$key]['user_name'] = $user_detail['username'];
			$resultArray[$key]['full_name'] = $user_detail['first_name'] . ' ' . $user_detail['last_name'];
		}

		if (!empty($resultArray)) {
			$resultArray = json_encode($resultArray);
			echo '{"status":"true","result":' . $resultArray . '}';
			die;
		} else
		echo '{"status":"false","message":"No Comment found"}';
		die;
	}
	function category()
	{

		$this->loadModel('Categories');
		$resultarray = array();
		$CategoryModel = $this->Categories->find()->where(['category_parent' => 0])->toArray();//all',array('conditions'=>array('category_parent'=>'0')));
		if (count($CategoryModel) > 0) {
			for ($i = 0; $i < count($CategoryModel); $i++) {
				$resultarray[$i] = array();
				$categoryId = $CategoryModel[$i]['id'];
				$resultarray[$i]['id'] = $categoryId;
				$resultarray[$i]['name'] = $CategoryModel[$i]['category_name'];
				$cat_image = $CategoryModel[$i]['category_webicon'];

				$category_webimage = $CategoryModel[$i]['category_webimage'];
					//$resultarray[$i]['icon'] = SITE_URL.'images/category/'.$cat_image;
				if ($cat_image == "") {
					$resultarray[$i]['icon'] = SITE_URL . 'media/avatars/thumb70/usrimg.jpg';
				} else {

						//$imageName = $photo['image_name'];

					$imageName = WWW_ROOT . 'images/category/' . $cat_image;

					if (file_exists($imageName)) {
						$resultarray[$i]['icon'] = SITE_URL . 'images/category/' . $cat_image;

					} else {
						$resultarray[$i]['icon'] = SITE_URL . 'media/avatars/thumb70/usrimg.jpg';
					}

				}

				if ($category_webimage == "") {
					$resultarray[$i]['category_image'] = SITE_URL . 'media/avatars/thumb70/usrimg.jpg';
				} else {

						//$imageName = $photo['image_name'];

					$webimageName = WWW_ROOT . 'images/category/' . $category_webimage;

					if (file_exists($webimageName)) {
						$resultarray[$i]['category_image'] = SITE_URL . 'images/category/' . $category_webimage;

					} else {
						$resultarray[$i]['category_image'] = SITE_URL . 'media/avatars/thumb70/usrimg.jpg';
					}

				}
				$resultarray[$i]['subcategory'] = array();
				$subcategoryModel = $this->Categories->find()->where(['category_parent' => $categoryId])->andWhere(['category_sub_parent' => 0])->toArray();
				if (count($subcategoryModel) > 0) {
					for ($j = 0; $j < count($subcategoryModel); $j++) {
						$subcatid = $subcategoryModel[$j]['id'];
						$subname = $subcategoryModel[$j]['category_name'];
						$resultarray[$i]['subcategory'][$j] = array();
						$resultarray[$i]['subcategory'][$j]['id'] = $subcatid;
						$resultarray[$i]['subcategory'][$j]['name'] = $subname;

					}
				}
			}

			if (!empty($resultarray)) {
				return $resultarray = json_encode($resultarray);
			} else
			echo '{"status":"false","message":"No Category found"}';
			die;

		}

	}
	function popularProducts($offset = null, $limit = null, $layout = null)
	{

		$this->loadModel('Items');
		$this->loadModel('Followers');
		$favitems_ids = array();
		$items_data = array();

		if ($layout = 'slider2' && !isset($offset)) {
			$offset = 0;
			$limit = 20;
		} elseif(!isset($layout) && isset($offset)){
			$offset = $offset;
			$limit = $limit;
		}elseif(!isset($layout) && !isset($offset)){
			$offset = 0;
			$limit = 10;
		}
		if (isset($offset)) {
			$items_data = $this->Items->find('all', array(
				'conditions' => array(
					'Items.status' => 'publish'
				),
				'limit' => $limit,
				'offset' => $offset,
				'order' => 'fav_count DESC',
			))->contain('Forexrates')->where(['Items.affiliate_commission IS NULL']);
		} else {
			$items_data = $this->Items->find('all', array(
				'conditions' => array(
					'Items.status' => 'publish'
				),
				'limit' => $limit,
				'offset' => $offset,
				'order' => 'fav_count DESC',
			))->contain('Forexrates')->where(['Items.affiliate_commission IS NULL']);

		}

		if (empty($items_data)) {
			echo '{"status":"false","message":"No data found"}';
			die;
		} else {
			$resultArray = $this->convertJsonHome($items_data, $favitems_ids, $_POST['user_id']);
			//die;
			return $resultArray;
		}
	}

	public function convertJsonHome($items_data, $favitems_ids = null, $user_id = null, $temp = null,$type=null)
	{
		$this->loadModel('Contactsellers');
		$this->loadModel('Itemfavs');
		$this->loadModel('Sitequeries');
		$this->loadModel('Facebookcoupons');
		$this->loadModel('Forexrates');
		$this->loadModel('Photos');
		$this->loadModel('Users');
		$this->loadModel('Sitesettings');
		$this->loadModel('Itemreviews');
		$setngs = $this->Sitesettings->find()->toArray();
		$photos = $this->Photos->find()->order(['id DESC'])->all();

		$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();

		$resultArray = array();
		$resultArray['type'] = "Everything";
		if ($type != null)
			$resultArray['type'] = $type;
		$resultArray = array();
		$shareCouponDetail = array();

		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}
		$userDetail = $this->Users->find()->where(['id' => $user_id])->first();

		if (!empty($userDetail))
			$userId = $userDetail['id'];
		else
			$userId = $user_id;

		$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();
		if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "" || count($userDetail) == 0) {
			$cur_symbol = $forexrateModel['currency_symbol'];
			$cur = $forexrateModel['price'];
		} else {
			$cur_symbol = $currency_value['currency_symbol'];
			$cur = $currency_value['price'];
		}

		//echo $user_id; die;
		$items_fav_data = $this->Itemfavs->find()->where(['user_id' => $user_id])->all();

		$favitems_ids = array();
		if (count($items_fav_data) > 0) {
			foreach ($items_fav_data as $favitems) {
				
				$favitems_ids[] = $favitems->item_id;
			}
		} else {
			$favitems_ids = array();
		}

		//echo '<pre>'; print_r($favitems); die;
		
		
		foreach ($items_data as $key => $listitem) {


			if(isset($listitem['related_products']))
			{
				//echo 'test'; die;	
					$resultArray[$key]['related_items'] = $this->convertJsonHomesuggested($listitem['related_products'], $favitems_ids, $user_id);					
				
				
			}elseif(isset($listitem['id']))
			{
				$sizeprice = [];

			$reportUsers = '';
			$process_time = $listitem['processing_time'];
			if ($process_time == '1d') {
				$process_time = "One business day";
			} elseif ($process_time == '2d') {
				$process_time = "Two business days";
			} elseif ($process_time == '3d') {
				$process_time = "Three business days";
			} elseif ($process_time == '4d') {
				$process_time = "Four business days";
			} elseif ($process_time == '2ww') {
				$process_time = "One-Two weeks";
			} elseif ($process_time == '3w') {
				$process_time = "Two-Three weeks";
			} elseif ($process_time == '4w') {
				$process_time = "Three-Four weeks";
			} elseif ($process_time == '6w') {
				$process_time = "Four-Six weeks";
			} elseif ($process_time == '8w') {
				$process_time = "Six-Eight weeks";
			}
			$shareSeller = $listitem['share_coupon'];

			$shareCouponDetail = $this->Facebookcoupons->find()->where(['item_id' => $listitem['id']])->andWhere(['user_id' => $user_id])->all();//all',array('conditions'=>array('Facebookcoupon.item_id'=> $listitem['Item']['id'] , 'Facebookcoupon.user_id'=> $userId )));
			if (count($shareCouponDetail) != 0)
				$shareUser = "yes";
			else
				$shareUser = "no";

			$resultArray[$key]['id'] = $listitem['id'];
			$resultArray[$key]['item_title'] = $listitem['item_title'];
			$resultArray[$key]['item_description'] = $listitem['item_description'];
			$resultArray[$key]['currency'] = $cur_symbol;
			$resultArray[$key]['average_rating'] = $listitem['avg_rating'];
			$totalreviews = $this->Itemreviews->find()->where(['itemid' => $listitem['id']])->all();
			$resultArray[$key]['review_count'] = count($totalreviews);

			if ($listitem['size_options'] != "") {
				$sizes = json_decode($listitem['size_options'], true);

				//	if($sizes!=""){

				foreach ($sizes['price'] as $key1 => $value) {

					$sizeprice[] = $value;
				}

				$resultArray[$key]['mainprice'] = $sizeprice[0];
				$price = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $sizeprice[0]);
				$resultArray[$key]['price'] = $price;

					//}
			} else {

				$resultArray[$key]['mainprice'] = $listitem['price'];
				$price = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $listitem['price']);
				$resultArray[$key]['price'] = $price;
			}

			$today = strtotime(date("Y-m-d"));
			$dealdate1 = date("Y-m-d", strtotime($listitem['dealdate']));
			$dealDate = strtotime($dealdate1);
			if(!empty($listitem['affiliate_commission']) && $listitem['affiliate_commission'] > 0) {
			$resultArray[$key]['commision_percentage'] = $listitem['affiliate_commission'];
				}

			if ($dealDate == $today && $listitem['discount_type'] == 'daily') {
				$resultArray[$key]['deal_enabled'] = 'yes';
				$resultArray[$key]['pro_discount'] = 'dailydeal';
				$resultArray[$key]['discount_percentage'] = $listitem['discount'];
				$resultArray[$key]['valid_till'] = (!isset($listitem['dealdate'])) ? '' : strtotime($listitem['dealdate']) ;
			} elseif($listitem['discount_type'] == 'regular') {
				$resultArray[$key]['deal_enabled'] = 'yes';
				$resultArray[$key]['pro_discount'] = 'regulardeal';
				$resultArray[$key]['discount_percentage'] = $listitem['discount'];
				$resultArray[$key]['valid_till'] = (!isset($listitem['dealdate'])) ? '' : strtotime($listitem['dealdate']) ;
			}else{
				$resultArray[$key]['deal_enabled'] = 'no';
				$resultArray[$key]['discount_percentage'] = 0;
				$resultArray[$key]['valid_till'] = (!isset($listitem['dealdate'])) ? '' : strtotime($listitem['dealdate']) ;
			}

			/*
			if ($listitem['dailydeal'] == 'yes' && $dealDate == $today) {
				$discount = $listitem['discount'];
				$dealdate = date("Y-m-d", strtotime($listitem['dealdate']));//.' 24:00:00';
				$dealdate = strtotime($dealdate);

				$resultArray[$key]['deal_enabled'] = 'yes';
				$resultArray[$key]['discount_percentage'] = $discount;
				$resultArray[$key]['valid_till'] = $dealdate;
			} else {
				$resultArray[$key]['deal_enabled'] = 'no';
				$resultArray[$key]['discount_percentage'] = "";
				$resultArray[$key]['valid_till'] = "";
			}
			*/



			$resultArray[$key]['quantity'] = $listitem['quantity'];
			$resultArray[$key]['cod'] = $listitem['cod'];

			if (in_array($listitem['id'], $favitems_ids)) {
				$resultArray[$key]['liked'] = 'yes';
			} else {
				$resultArray[$key]['liked'] = 'no';
			}

			$item_status = json_decode($listitem['report_flag'], true); //print_r($item_status); die;
			if(!empty($item_status)) {
			if (in_array($userId, $item_status)) {
				$report_status = "yes";
			} else {
				$report_status = "no";

			}
			} else {
				$report_status = "no";
			}
			if ($listitem['featured'] == 1)
				$convertdefaultprice = round(($listitem['price'] * 2) * $listitem['price'], 2);
			else
				$convertdefaultprice = round($listitem['price'] * $listitem['price'], 2);
			$resultArray[$key]['report'] = $report_status;
			$likedcount = $this->Itemfavs->find()->where(['item_id' => $listitem['id']])->count();
			$resultArray[$key]['like_count'] = $likedcount;
			$resultArray[$key]['fbshare_discount'] = $listitem['share_discountAmount'];
			$resultArray[$key]['reward_points'] = floor($convertdefaultprice);
			$resultArray[$key]['share_seller'] = $shareSeller;
			$resultArray[$key]['share_user'] = $shareUser;
			if ($listitem['status'] == 'publish') {
				$resultArray[$key]['approve'] = true;
			} else {
				$resultArray[$key]['approve'] = false;
			}
			if ($listitem['status'] == 'things') {
				$resultArray[$key]['buy_type'] = "affiliate";
			} else if ($listitem['status'] == 'publish') {
				$resultArray[$key]['buy_type'] = "buy";
			}

			
			$resultArray[$key]['affiliate_link'] = $listitem['bm_redircturl'];
			$resultArray[$key]['shipping_time'] = $process_time;
				//$resultArray[$key]['product_url'] = SITE_URL.'listing/'.$listitem['id'].'/'.$listitem['item_title_url'];
			$itemid = base64_encode($listitem['id'] . "_" . rand(1, 9999));
			$itemshareid = base64_encode($listitem['id'] . "_" . rand(1, 9999) . "_". $user_id);
			$resultArray[$key]['product_url'] = SITE_URL . 'listing/' . $itemid;
			$resultArray[$key]['product_share_url'] = SITE_URL . 'listing/' . $itemshareid;
			if ($temp == 1) {
				$resultArray[$key]['size'] = [];
				if (empty($listitem['size_options'])) {//size":[{"name":"No size","qty":"100","price":"91"}]
				$resultArray[$key]['size'][0]['name'] = "";
				$resultArray[$key]['size'][0]['qty'] = $listitem['quantity'];
				$price = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $listitem['price']);
				$resultArray[$key]['size'][0]['price'] = $price;
			} else {
				$sizes = json_decode($listitem['size_options'], true);
				$sqkey = 0;
				foreach ($sizes['size'] as $val) {
					if (count($sizes['unit'][$val]) > 0) {
						$resultArray[$key]['size'][$sqkey]['name'] = $val;
						$resultArray[$key]['size'][$sqkey]['qty'] = $sizes['unit'][$val];
						$price = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $sizes['price'][$val]);
						$resultArray[$key]['size'][$sqkey]['price'] = $price;
						$sqkey++;
					}
				}
			}
		}

			$sitequeriesModel = $this->Sitequeries->find()->where(['type' => 'contact_seller'])->first();//
			$csqueries = json_decode($sitequeriesModel['queries'], true);



			foreach ($photos as $keys => $photo) {
				$itemIds[] = $photo['item_id'];

				if ($listitem['id'] == $photo['item_id']) {
					$imageName = $photo['image_name'];
					if ($imageName == '') {
						$imageName = "usrimg.jpg";
					}

					if ($keys == 0) {
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb70/' . $photo['image_name'];
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb70/' . $imageName;
					} else {
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb70/' . $photo['image_name'];
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb70/' . $imageName;
					}

					if ($keys == 0) {
						$image = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						list($width, $height) = getimagesize($image);
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $imageName;
						$resultArray[$key]['height'] = $height;
						$resultArray[$key]['width'] = $width;
					} else {
						$image = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						list($width, $height) = getimagesize($image);
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $imageName;
						$resultArray[$key]['height'] = $height;
						$resultArray[$key]['width'] = $width;
					}

					if ($keys == 0) {
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $imageName;
					} else {
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $imageName;
					}
				}

			}

			if (!in_array($listitem['id'], $itemIds)) {
				$image = $img_path . 'media/items/thumb350/usrimg.jpg';
				list($width, $height) = getimagesize($image);
				$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/usrimg.jpg';
				$resultArray[$key]['height'] = $height;
				$resultArray[$key]['width'] = $width;
			}
			}

		}
		//print_r($resultArray); die;
		return array_values($resultArray);
	}


	public function convertJsonHomenew($items_data, $favitems_ids = null, $user_id = null, $temp = null)
	{

		$this->loadModel('Contactsellers');
		$this->loadModel('Itemfavs');
		$this->loadModel('Sitequeries');
		$this->loadModel('Facebookcoupons');
		$this->loadModel('Forexrates');
		$this->loadModel('Photos');
		$this->loadModel('Users');
		$this->loadModel('Sitesettings');
		$this->loadModel('Itemreviews');
		$setngs = $this->Sitesettings->find()->toArray();
		$photos = $this->Photos->find()->order(['id DESC'])->all();

		$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();

		$resultArray = array();
		$resultArray['type'] = "Everything";
		if ($type != null)
			$resultArray['type'] = $type;
		$resultArray = array();
		$shareCouponDetail = array();

		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}
		$userDetail = $this->Users->find()->where(['id' => $user_id])->first();

		if (!empty($userDetail))
			$userId = $userDetail['id'];
		else
			$userId = $userId;

		$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();
		if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "" || count($userDetail) == 0) {
			$cur_symbol = $forexrateModel['currency_symbol'];
			$cur = $forexrateModel['price'];
		} else {
			$cur_symbol = $currency_value['currency_symbol'];
			$cur = $currency_value['price'];
		}
		$items_fav_data = $this->Itemfavs->find()->where(['user_id' => $user_id])->all();//
		if (count($items_fav_data) > 0) {
			foreach ($items_fav_data as $favitems) {
				$favitems_ids[] = $favitems['item_id'];
			}
		} else {
			$favitems_ids = array();
		}
		
		foreach ($items_data as $key => $listitem) {

				if(!empty($listitem)) {

			$sizeprice = [];

			$reportUsers = '';
			$process_time = $listitem['processing_time'];
			if ($process_time == '1d') {
				$process_time = "One business day";
			} elseif ($process_time == '2d') {
				$process_time = "Two business days";
			} elseif ($process_time == '3d') {
				$process_time = "Three business days";
			} elseif ($process_time == '4d') {
				$process_time = "Four business days";
			} elseif ($process_time == '2ww') {
				$process_time = "One-Two weeks";
			} elseif ($process_time == '3w') {
				$process_time = "Two-Three weeks";
			} elseif ($process_time == '4w') {
				$process_time = "Three-Four weeks";
			} elseif ($process_time == '6w') {
				$process_time = "Four-Six weeks";
			} elseif ($process_time == '8w') {
				$process_time = "Six-Eight weeks";
			}
			$shareSeller = $listitem['share_coupon'];

			$shareCouponDetail = $this->Facebookcoupons->find()->where(['item_id' => $listitem['id']])->andWhere(['user_id' => $user_id])->all();//all',array('conditions'=>array('Facebookcoupon.item_id'=> $listitem['Item']['id'] , 'Facebookcoupon.user_id'=> $userId )));
			if (count($shareCouponDetail) != 0)
				$shareUser = "yes";
			else
				$shareUser = "no";

			$resultArray[$key]['id'] = $listitem['id'];
			$resultArray[$key]['item_title'] = $listitem['item_title'];
			$resultArray[$key]['item_description'] = $listitem['item_description'];
			$resultArray[$key]['currency'] = $cur_symbol;
			$resultArray[$key]['average_rating'] = $listitem['avg_rating'];
			$totalreviews = $this->Itemreviews->find()->where(['itemid' => $listitem['id']])->all();
			$resultArray[$key]['review_count'] = count($totalreviews);


			if(isset($listitem['related_products']))
			{
				//echo '<pre>'; print_r($listitem); die;
				//$arraylists =  (array)$listitem;
				$resultArray[$key]['related_items'] = $this->convertJsonHomesuggested($listitem['related_products'], $favitems_ids, $_POST['user_id']);				
			}


			if ($listitem['size_options'] != "") {
				$sizes = json_decode($listitem['size_options'], true);

				//	if($sizes!=""){

				foreach ($sizes['price'] as $key1 => $value) {

					$sizeprice[] = $value;
				}

				$resultArray[$key]['mainprice'] = (!isset($sizeprice[0])) ? '' : $sizeprice[0];
				$price = $this->Currency->conversion($listitem['forexrate']->price, $cur, $sizeprice[0]);
				$resultArray[$key]['price'] = (!isset($price)) ? '' : $price;

					//}
			} else {
				
				$resultArray[$key]['mainprice'] = (!isset($listitem['price'])) ? '' : $listitem['price'];
				$price = $this->Currency->conversion($listitem['forexrate']->price, $cur, $listitem['price']);
				$resultArray[$key]['price'] = $price;
			}

			$today = strtotime(date("Y-m-d"));
			$dealdate1 = date("Y-m-d", strtotime($listitem['dealdate']));
			$dealDate = strtotime($dealdate1);
			if ($dealDate == $today && $listitem['discount_type'] == 'daily') {
				$resultArray[$key]['deal_enabled'] = 'yes';
				$resultArray[$key]['pro_discount'] = 'dailydeal';
				$resultArray[$key]['discount_percentage'] = $listitem['discount'];
				$resultArray[$key]['valid_till'] = (!isset($listitem['dealdate'])) ? '' : strtotime($listitem['dealdate']) ;
			} elseif($listitem['discount_type'] == 'regular') {
				$resultArray[$key]['deal_enabled'] = 'yes';
				$resultArray[$key]['pro_discount'] = 'regulardeal';
				$resultArray[$key]['discount_percentage'] = $listitem['discount'];
				$resultArray[$key]['valid_till'] = (!isset($listitem['dealdate'])) ? '' : strtotime($listitem['dealdate']) ;
			}else{
				$resultArray[$key]['deal_enabled'] = 'no';
				$resultArray[$key]['discount_percentage'] = 0;
				$resultArray[$key]['valid_till'] = (!isset($listitem['dealdate'])) ? '' : strtotime($listitem['dealdate']) ;
			}
			$resultArray[$key]['quantity'] = $listitem['quantity'];
			$resultArray[$key]['cod'] = $listitem['cod'];

			if (in_array($listitem['id'], $favitems_ids)) {
				$resultArray[$key]['liked'] = 'yes';
			} else {
				$resultArray[$key]['liked'] = 'no';
			}

			$item_status = json_decode($listitem['report_flag'], true); //print_r($item_status); die;

			if (in_array($userId, $item_status)) {
				$report_status = "yes";
			} else {
				$report_status = "no";

			}

			if ($listitem['featured'] == 1)
			$convertdefaultprice = round(($listitem['price'] * 2) * $listitem['price'], 2);
			else
			$convertdefaultprice = round($listitem['price'] * $listitem['price'], 2);
			$resultArray[$key]['report'] = $report_status;
			$likedcount = $this->Itemfavs->find()->where(['item_id' => $listitem['id']])->count();
			$resultArray[$key]['like_count'] = $likedcount;
			$resultArray[$key]['fbshare_discount'] = $listitem['share_discountAmount'];
			$resultArray[$key]['reward_points'] = floor($convertdefaultprice);
			$resultArray[$key]['share_seller'] = $shareSeller;
			$resultArray[$key]['share_user'] = $shareUser;
			if ($listitem['status'] == 'publish') {
				$resultArray[$key]['approve'] = true;
			} else {
				$resultArray[$key]['approve'] = false;
			}
			if ($listitem['status'] == 'things') {
				$resultArray[$key]['buy_type'] = "affiliate";
			} else if ($listitem['status'] == 'publish') {
				$resultArray[$key]['buy_type'] = "buy";
			}

			
			$resultArray[$key]['affiliate_link'] = $listitem['bm_redircturl'];
			$resultArray[$key]['shipping_time'] = $process_time;
				//$resultArray[$key]['product_url'] = SITE_URL.'listing/'.$listitem['id'].'/'.$listitem['item_title_url'];
			$itemid = base64_encode($listitem['id'] . "_" . rand(1, 9999));
			$resultArray[$key]['product_url'] = SITE_URL . 'listing/' . $itemid;
			if ($temp == 1) {
				$resultArray[$key]['size'] = [];
				if (empty($listitem['size_options'])) {//size":[{"name":"No size","qty":"100","price":"91"}]
				$resultArray[$key]['size'][0]['name'] = "";
				$resultArray[$key]['size'][0]['qty'] = $listitem['quantity'];
				$price = $this->Currency->conversion($listitem['forexrate']->price, $cur, $listitem['price']);
				$resultArray[$key]['size'][0]['price'] = $price;
			} else {
				$sizes = json_decode($listitem['size_options'], true);
				$sqkey = 0;
				foreach ($sizes['size'] as $val) {
					if (count($sizes['unit'][$val]) > 0) {
						$resultArray[$key]['size'][$sqkey]['name'] = $val;
						$resultArray[$key]['size'][$sqkey]['qty'] = $sizes['unit'][$val];
						$price = $this->Currency->conversion($listitem['forexrate']->price, $cur, $sizes['price'][$val]);
						$resultArray[$key]['size'][$sqkey]['price'] = $price;
						$sqkey++;
					}
				}
			}
		}

			$sitequeriesModel = $this->Sitequeries->find()->where(['type' => 'contact_seller'])->first();//
			$csqueries = json_decode($sitequeriesModel['queries'], true);

			foreach ($photos as $keys => $photo) {
				$itemIds[] = $photo['item_id'];

				if ($listitem['id'] == $photo['item_id']) {
					$imageName = $photo['image_name'];
					if ($imageName == '') {
						$imageName = "usrimg.jpg";
					}

					if ($keys == 0) {
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb70/' . $photo['image_name'];
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb70/' . $imageName;
					} else {
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb70/' . $photo['image_name'];
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb70/' . $imageName;
					}

					if ($keys == 0) {
						$image = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						list($width, $height) = getimagesize($image);
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $imageName;
						$resultArray[$key]['height'] = $height;
						$resultArray[$key]['width'] = $width;
					} else {
						$image = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						list($width, $height) = getimagesize($image);
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $imageName;
						$resultArray[$key]['height'] = $height;
						$resultArray[$key]['width'] = $width;
					}

					if ($keys == 0) {
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $imageName;
					} else {
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $imageName;
					}
				}

			}
			if (!in_array($listitem['id'], $itemIds)) {
				$image = $img_path . 'media/items/thumb350/usrimg.jpg';
				list($width, $height) = getimagesize($image);
				$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/usrimg.jpg';
				$resultArray[$key]['height'] = $height;
				$resultArray[$key]['width'] = $width;
			}

		}
	}

		return array_values($resultArray);
	}


	public function convertJsonHomesuggested($items_data, $favitems_ids = null, $user_id = null, $temp = null)
	{

		$this->loadModel('Contactsellers');
		$this->loadModel('Itemfavs');
		$this->loadModel('Sitequeries');
		$this->loadModel('Facebookcoupons');
		$this->loadModel('Forexrates');
		$this->loadModel('Photos');
		$this->loadModel('Users');
		$this->loadModel('Sitesettings');
		$this->loadModel('Itemreviews');
		$setngs = $this->Sitesettings->find()->toArray();
		$photos = $this->Photos->find()->order(['id DESC'])->all();

		$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();

		$resultArray = array();
		$resultArray['type'] = "Everything";
		if ($type != null)
			$resultArray['type'] = $type;
		$resultArray = array();
		$shareCouponDetail = array();

		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}
		$userDetail = $this->Users->find()->where(['id' => $user_id])->first();

		if (!empty($userDetail))
			$userId = $userDetail['id'];
		else
			$userId = $userId;

		$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();
		if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "" || count($userDetail) == 0) {
			$cur_symbol = $forexrateModel['currency_symbol'];
			$cur = $forexrateModel['price'];
		} else {
			$cur_symbol = $currency_value['currency_symbol'];
			$cur = $currency_value['price'];
		}
		$items_fav_data = $this->Itemfavs->find()->where(['user_id' => $user_id])->all();//
		if (count($items_fav_data) > 0) {
			foreach ($items_fav_data as $favitems) {
				$favitems_ids[] = $favitems['item_id'];
			}
		} else {
			$favitems_ids = array();
		}
		foreach ($items_data as $key => $listitem) {
			$sizeprice = [];

			$reportUsers = '';
			$process_time = $listitem['processing_time'];
			if ($process_time == '1d') {
				$process_time = "One business day";
			} elseif ($process_time == '2d') {
				$process_time = "Two business days";
			} elseif ($process_time == '3d') {
				$process_time = "Three business days";
			} elseif ($process_time == '4d') {
				$process_time = "Four business days";
			} elseif ($process_time == '2ww') {
				$process_time = "One-Two weeks";
			} elseif ($process_time == '3w') {
				$process_time = "Two-Three weeks";
			} elseif ($process_time == '4w') {
				$process_time = "Three-Four weeks";
			} elseif ($process_time == '6w') {
				$process_time = "Four-Six weeks";
			} elseif ($process_time == '8w') {
				$process_time = "Six-Eight weeks";
			}
			$shareSeller = $listitem['share_coupon'];

			$shareCouponDetail = $this->Facebookcoupons->find()->where(['item_id' => $listitem['id']])->andWhere(['user_id' => $user_id])->all();//all',array('conditions'=>array('Facebookcoupon.item_id'=> $listitem['Item']['id'] , 'Facebookcoupon.user_id'=> $userId )));
			if (count($shareCouponDetail) != 0)
				$shareUser = "yes";
			else
				$shareUser = "no";

			$resultArray[$key]['id'] = $listitem['id'];
			$resultArray[$key]['item_title'] = $listitem['item_title'];
			$resultArray[$key]['item_description'] = $listitem['item_description'];
			$resultArray[$key]['currency'] = $cur_symbol;
			$resultArray[$key]['average_rating'] = $listitem['avg_rating'];
			$totalreviews = $this->Itemreviews->find()->where(['itemid' => $listitem['id']])->all();
			$resultArray[$key]['review_count'] = count($totalreviews);
			if ($listitem['size_options'] != "") {
				$sizes = json_decode($listitem['size_options'], true);

				//	if($sizes!=""){

				foreach ($sizes['price'] as $key1 => $value) {

					$sizeprice[] = $value;
				}

				$resultArray[$key]['mainprice'] = $sizeprice[0];
				$price = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $sizeprice[0]);
				$resultArray[$key]['price'] = $price;

					//}
			} else {
				$resultArray[$key]['mainprice'] = $listitem['price'];
				$price = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $listitem['price']);
				$resultArray[$key]['price'] = $price;
			}
			$today = strtotime(date("Y-m-d"));
			$dealdate1 = date("Y-m-d", strtotime($listitem['dealdate']));
			$dealDate = strtotime($dealdate1);
			if ($listitem['dailydeal'] == 'yes' && $dealDate == $today) {
				$discount = $listitem['discount'];
				$dealdate = date("Y-m-d", strtotime($listitem['dealdate']));//.' 24:00:00';
				$dealdate = strtotime($dealdate);

				$resultArray[$key]['deal_enabled'] = 'yes';
				$resultArray[$key]['discount_percentage'] = $discount;
				$resultArray[$key]['valid_till'] = $dealdate;
			} else {
				$resultArray[$key]['deal_enabled'] = 'no';
				$resultArray[$key]['discount_percentage'] = "";
				$resultArray[$key]['valid_till'] = "";
			}
			$resultArray[$key]['quantity'] = $listitem['quantity'];
			$resultArray[$key]['cod'] = $listitem['cod'];

			if (in_array($listitem['id'], $favitems_ids)) {
				$resultArray[$key]['liked'] = 'yes';
			} else {
				$resultArray[$key]['liked'] = 'no';
			}

			$item_status = json_decode($listitem['report_flag'], true); //print_r($item_status); die;

			if (in_array($userId, $item_status)) {
				$report_status = "yes";
			} else {
				$report_status = "no";

			}

			if ($listitem['featured'] == 1)
			$convertdefaultprice = round(($listitem['price'] * 2) * $listitem['price'], 2);
			else
			$convertdefaultprice = round($listitem['price'] * $listitem['price'], 2);
			$resultArray[$key]['report'] = $report_status;
			$likedcount = $this->Itemfavs->find()->where(['item_id' => $listitem['id']])->count();
			$resultArray[$key]['like_count'] = $likedcount;
			$resultArray[$key]['fbshare_discount'] = $listitem['share_discountAmount'];
			$resultArray[$key]['reward_points'] = floor($convertdefaultprice);
			$resultArray[$key]['share_seller'] = $shareSeller;
			$resultArray[$key]['share_user'] = $shareUser;
			if ($listitem['status'] == 'publish') {
				$resultArray[$key]['approve'] = true;
			} else {
				$resultArray[$key]['approve'] = false;
			}
			if ($listitem['status'] == 'things') {
				$resultArray[$key]['buy_type'] = "affiliate";
			} else if ($listitem['status'] == 'publish') {
				$resultArray[$key]['buy_type'] = "buy";
			}

			$resultArray[$key]['affiliate_link'] = $listitem['bm_redircturl'];
			$resultArray[$key]['shipping_time'] = $process_time;
				//$resultArray[$key]['product_url'] = SITE_URL.'listing/'.$listitem['id'].'/'.$listitem['item_title_url'];
			$itemid = base64_encode($listitem['id'] . "_" . rand(1, 9999));
			$resultArray[$key]['product_url'] = SITE_URL . 'listing/' . $itemid;
			if ($temp == 1) {
				$resultArray[$key]['size'] = [];
				if (empty($listitem['size_options'])) {//size":[{"name":"No size","qty":"100","price":"91"}]
				$resultArray[$key]['size'][0]['name'] = "";
				$resultArray[$key]['size'][0]['qty'] = $listitem['quantity'];
				$price = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $listitem['price']);
				$resultArray[$key]['size'][0]['price'] = $price;
			} else {
				$sizes = json_decode($listitem['size_options'], true);
				$sqkey = 0;
				foreach ($sizes['size'] as $val) {
					if (count($sizes['unit'][$val]) > 0) {
						$resultArray[$key]['size'][$sqkey]['name'] = $val;
						$resultArray[$key]['size'][$sqkey]['qty'] = $sizes['unit'][$val];
						$price = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $sizes['price'][$val]);
						$resultArray[$key]['size'][$sqkey]['price'] = $price;
						$sqkey++;
					}
				}
			}
		}

			$sitequeriesModel = $this->Sitequeries->find()->where(['type' => 'contact_seller'])->first();//
			$csqueries = json_decode($sitequeriesModel['queries'], true);

			foreach ($photos as $keys => $photo) {
				$itemIds[] = $photo['item_id'];

				if ($listitem['id'] == $photo['item_id']) {
					$imageName = $photo['image_name'];
					if ($imageName == '') {
						$imageName = "usrimg.jpg";
					}

					if ($keys == 0) {
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb70/' . $photo['image_name'];
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb70/' . $imageName;
					} else {
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb70/' . $photo['image_name'];
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb70/' . $imageName;
					}

					if ($keys == 0) {
						$image = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						list($width, $height) = getimagesize($image);
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $imageName;
						$resultArray[$key]['height'] = $height;
						$resultArray[$key]['width'] = $width;
					} else {
						$image = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						list($width, $height) = getimagesize($image);
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $imageName;
						$resultArray[$key]['height'] = $height;
						$resultArray[$key]['width'] = $width;
					}

					if ($keys == 0) {
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $imageName;
					} else {
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/' . $imageName;
					}
				}

			}
			if (!in_array($listitem['id'], $itemIds)) {
				$image = $img_path . 'media/items/thumb350/usrimg.jpg';
				list($width, $height) = getimagesize($image);
				$resultArray[$key]['image'] = $img_path . 'media/items/thumb350/usrimg.jpg';
				$resultArray[$key]['height'] = $height;
				$resultArray[$key]['width'] = $width;
			}

		}

		return $resultArray;
	}


	public function item_details($value='')
	{
		$this->loadModel('Contactsellers');
		$this->loadModel('Itemfavs');
		$this->loadModel('Sitequeries');
		$this->loadModel('Facebookcoupons');
		$this->loadModel('Sellercoupons');
		$this->loadModel('Itemreviews');
		$this->loadModel('Forexrates');
		$this->loadModel('Photos');
		$this->loadModel('Users');
		$this->loadModel('Sitesettings');

		$setngs = $this->Sitesettings->find()->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}

		$listitem = $this->Items->find('all', array(
				'conditions' => array(
					'Items.id' => $value,
					'Items.status' => 'publish'
				)
			))->contain('Forexrates')->first();

		if(empty($listitem))
			return false;

		$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();
		if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "" || count($userDetail) == 0) {
			$cur_symbol = $forexrateModel['currency_symbol'];
			$cur = $forexrateModel['price'];
		} else {
			$cur_symbol = $currency_value['currency_symbol'];
			$cur = $currency_value['price'];
		}
		//echo '<pre>'; print_r($items_data); die;

		$items_fav_data = $this->Itemfavs->find()->where(['user_id' => $user_id])->all();
		if (count($items_fav_data) > 0) {
			foreach ($items_fav_data as $favitems) {
				$favitems_ids[] = $favitems['item_id'];
			}
		} else {
			$favitems_ids = array();
		}
		
		$resultArray = array();
		$resultArray['id'] = $listitem['id'];
		$resultArray['item_status'] = $listitem['status'];
		$resultArray['item_title'] = $listitem['item_title'];
		$resultArray['currency'] = (!isset($cur_symbol)) ? "" : $cur_symbol;

		
			if ($listitem['size_options'] != "") {
				
				$sizes = json_decode($listitem['size_options'], true);


				//	if($sizes!=""){

				foreach ($sizes['price'] as $key1 => $value) {

					$sizeprice[] = $value;
				}

				$resultArray['mainprice'] = $sizeprice[0];
				$price = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $sizeprice[0]);
				$resultArray['price'] = $price;

					//}
			} else {

				$resultArray['mainprice'] = $listitem['price'];
				$price = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $listitem['price']);
				$resultArray['price'] = $price;
			}
			$today = strtotime(date("Y-m-d"));
			$dealdate1 = date("Y-m-d", strtotime($listitem['dealdate']));
			$dealDate = strtotime($dealdate1);

			
			if ($listitem['dailydeal'] == 'yes' && $dealDate == $today) {
				$discount = $listitem['discount'];
				$dealdate = date("Y-m-d", strtotime($listitem['dealdate']));//.' 24:00:00';
				$dealdate = strtotime($dealdate);

				$resultArray['deal_enabled'] = 'yes';
				$resultArray['discount_percentage'] = $discount;
				$resultArray['valid_till'] = $dealdate;
			} else {
				$resultArray['deal_enabled'] = 'no';
				$resultArray['discount_percentage'] = "";
				$resultArray['valid_till'] = "";
			}

			$resultArray['quantity'] = $listitem['quantity'];
			$resultArray['cod'] = $listitem['cod'];

			$itemDiscount = $this->Sellercoupons->find()->where([
			'sourceid' => $listitem['id'],
			'type'=>'item',
			'remainrange !='=>'0'
			])->order(['id'=>'desc'])->first();

			$categoryDiscount = $this->Sellercoupons->find()->where([
				'sourceid' => $listitem['category_id'],
				'sellerid'=>$listitem['user_id'],
				'type'=>'category',
				'remainrange !='=>'0'
				])->order(['id'=>'desc'])->first();

			//print_r($categoryDiscount); die;

			if((!empty($itemDiscount))){
			$resultArray['seller_offer']['couponcode'] = $itemDiscount->couponcode;
			$resultArray['seller_offer']['couponpercentage'] = $itemDiscount->couponpercentage;
			$resultArray['seller_offer']['validfrom'] = date("M d", strtotime($itemDiscount->validfrom));
			$resultArray['seller_offer']['validto'] = date("M d", strtotime($itemDiscount->validto));	
			$resultArray['seller_offer']['coupon_count'] = $itemDiscount->totalrange;
		}else{
			$resultArray['seller_offer'] = (object) array();
		}
		
		//xxx use this coupon to get extra 10% discount from April 10 to April 15. Limited for first 10 purchases only.
		if((!empty($categoryDiscount))){
			$resultArray['category_offer']['couponcode'] = $categoryDiscount->couponcode;
			$resultArray['category_offer']['couponpercentage'] = $categoryDiscount->couponpercentage;
			$resultArray['category_offer']['validfrom'] = date("M d", strtotime($categoryDiscount->validfrom));
			$resultArray['category_offer']['validto'] = date("M d", strtotime($categoryDiscount->validto));
			$resultArray['category_offer']['coupon_count'] = $categoryDiscount->totalrange;
		}else{
			$resultArray['category_offer'] = (object) array();
		}

		/*
			$resultArray['seller_offer'] = (!empty($itemDiscount)) ? $itemDiscount->couponcode.' use this coupon to get extra '.$itemDiscount->couponpercentage.'% discount from '.date("M d", strtotime($itemDiscount->validfrom)).' to '.date("M d", strtotime($itemDiscount->validto)).' limited for first '.$itemDiscount->totalrange : '';
			
			$resultArray['category_offer'] = (!empty($categoryDiscount)) ? $categoryDiscount->couponcode.' use this coupon to get extra '.$categoryDiscount->couponpercentage.'% discount from '.date("M d", strtotime($categoryDiscount->validfrom)).' to '.date("M d", strtotime($categoryDiscount->validto)).' limited for first '.$categoryDiscount->totalrange : '';
			*/

			$resultArray['admin_offer'] = '';


			$itemreviewTable = TableRegistry::get('Itemreviews');
			$reviewData = $this->Itemreviews->find('all', array(
					'conditions' => array(
						'itemid' => $value
					),
					'limit' => 2,
					'offset' => 0,
					'order' => 'id DESC',
				))->all();


			$reviewCount = $this->Itemreviews->find('all', array(
					'conditions' => array(
						'itemid' => $value
					),
					'order' => 'id DESC',
				))->count();


			$getAvgrat = $this->getAverage($value);
			$result = array();
			
			foreach($reviewData as $key=>$eachreview)
			{
				$user_data = $this->Users->find()->where(['id' => $eachreview['userid']])->first();
				$result[$key]['user_id'] = $eachreview['userid'];
				$result[$key]['user_name'] = $user_data['username'];
				$result[$key]['user_image'] = ($user_data['profile_image'] != '') ? $img_path . "media/avatars/thumb70/".$user_data['profile_image'] : $img_path . "media/avatars/thumb70/usrimg.jpg";
				$result[$key]['id'] = $eachreview['orderid'];
				$result[$key]['review_title'] = $eachreview['review_title'];
				$result[$key]['rating'] = $eachreview['ratings'];
				$result[$key]['review'] = $eachreview['reviews'];
			}

			$datanewSourceObject = ConnectionManager::get('default');
	    	$ratingstmt = $datanewSourceObject->execute("SELECT count(*) as Total, round(ratings) as ratings from fc_itemreviews where itemid=".$value." group by ratings order by ratings desc
			")->fetchAll('assoc');

	    	$byrateGroup = $this->group_by("ratings", $ratingstmt);

	    	//echo '<pre>'; print_r($byrateGroup); die;
			$rating_count = ($byrateGroup[5][0]['Total']+$byrateGroup[4][0]['Total']+$byrateGroup[3][0]['Total']+$byrateGroup[2][0]['Total']+$byrateGroup[1][0]['Total']);
			
			$five = (empty($byrateGroup[5][0]['Total'])) ? '' : $byrateGroup[5][0]['Total'] ;
			$four = (empty($byrateGroup[4][0]['Total'])) ? '' : $byrateGroup[4][0]['Total'] ;
			$three = (empty($byrateGroup[3][0]['Total'])) ? '' : $byrateGroup[3][0]['Total'] ;
			$two = (empty($byrateGroup[2][0]['Total'])) ? '' : $byrateGroup[2][0]['Total'] ;
			$one = (empty($byrateGroup[1][0]['Total'])) ? '' : $byrateGroup[1][0]['Total'] ;

			$resultArray['item_reviews'] = array(
				'review_count'=>$reviewCount,
				'rating'=>$getAvgrat['rating'],
				'rating_count'=>$rating_count,
				'five'=>$five,
				'four'=>$four,
				'three'=>$three,
				'two'=>$two,
				'one'=>$one,
				'result'=>$result
				);

			$inputArray = array('item_id'=>$value);
			$resultArray['recent_questions'] = $this->getlatestproduct_faq($inputArray);

			if (in_array($listitem['id'], $favitems_ids)) {
				$resultArray['liked'] = 'yes';
			} else {
				$resultArray['liked'] = 'no';
			}



			$item_status = json_decode($listitem['report_flag'], true); //print_r($item_status); die;

			if (in_array($userId, $item_status)) {
				$report_status = "yes";
			} else {
				$report_status = "no";

			}

			if ($listitem['featured'] == 1)
			$convertdefaultprice = round(($listitem['price'] * 2) * $listitem['price'], 2);
			else
			$convertdefaultprice = round($listitem['price'] * $listitem['price'], 2);

			$resultArray['report'] = $report_status;
			
			$likedcount = $this->Itemfavs->find()->where(['item_id' => $listitem['id']])->count();
			$resultArray['like_count'] = $likedcount;
			$resultArray['fbshare_discount'] = $listitem['share_discountAmount'];
			$resultArray['reward_points'] = floor($convertdefaultprice);
			$resultArray['share_seller'] = (!isset($shareSeller)) ? "" : $shareSeller;
			$resultArray['share_user'] = (!isset($shareUser)) ? "" : $shareUser;
			if ($listitem['status'] == 'publish') {
				$resultArray['approve'] = true;
			} else {
				$resultArray['approve'] = false;
			}
			if ($listitem['status'] == 'things') {
				$resultArray['buy_type'] = "affiliate";
			} else if ($listitem['status'] == 'publish') {
				$resultArray['buy_type'] = "buy";
			}

			$resultArray['affiliate_link'] = $listitem['bm_redircturl'];
			$resultArray['shipping_time'] = (!isset($process_time)) ? "" : $process_time;



				//$resultArray[$key]['product_url'] = SITE_URL.'listing/'.$listitem['id'].'/'.$listitem['item_title_url'];
			$itemid = base64_encode($listitem['id'] . "_" . rand(1, 9999));
			$resultArray['product_url'] = SITE_URL . 'listing/' . $itemid;
			if ($temp == 1) {
				$resultArray['size'] = [];
				if (empty($listitem['size_options'])) {//size":[{"name":"No size","qty":"100","price":"91"}]
				$resultArray['size'][0]['name'] = "";
				$resultArray['size'][0]['qty'] = $listitem['quantity'];
				$price = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $listitem['price']);
				$resultArray['size'][0]['price'] = $price;
			} else {
				$sizes = json_decode($listitem['size_options'], true);
				$sqkey = 0;
				foreach ($sizes['size'] as $val) {
					if (count($sizes['unit'][$val]) > 0) {
						$resultArray['size'][$sqkey]['name'] = $val;
						$resultArray['size'][$sqkey]['qty'] = $sizes['unit'][$val];
						$price = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $sizes['price'][$val]);
						$resultArray['size'][$sqkey]['price'] = $price;
						$sqkey++;
					}
				}
			}
		}

			$sitequeriesModel = $this->Sitequeries->find()->where(['type' => 'contact_seller'])->first();//
			$csqueries = json_decode($sitequeriesModel['queries'], true);

			foreach ($photos as $keys => $photo) {
				$itemIds[] = $photo['item_id'];

				if ($listitem['id'] == $photo['item_id']) {
					$imageName = $photo['image_name'];
					if ($imageName == '') {
						$imageName = "usrimg.jpg";
					}

					if ($keys == 0) {
						$resultArray['image'] = $img_path . 'media/items/thumb70/' . $photo['image_name'];
						$resultArray['image'] = $img_path . 'media/items/thumb70/' . $imageName;
					} else {
						$resultArray['image'] = $img_path . 'media/items/thumb70/' . $photo['image_name'];
						$resultArray['image'] = $img_path . 'media/items/thumb70/' . $imageName;
					}

					if ($keys == 0) {
						$image = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						list($width, $height) = getimagesize($image);
						$resultArray['image'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						$resultArray['image'] = $img_path . 'media/items/thumb350/' . $imageName;
						$resultArray['height'] = (!isset($height)) ? "" : $height;
						$resultArray['width'] = (!isset($width)) ? "" : $width;
					} else {
						$image = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						list($width, $height) = getimagesize($image);
						$resultArray['image'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						$resultArray['image'] = $img_path . 'media/items/thumb350/' . $imageName;
						$resultArray['height'] = (!isset($height)) ? "" : $height;
						$resultArray['width'] = (!isset($width)) ? "" : $width;
					}

					if ($keys == 0) {
						$resultArray['image'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						$resultArray['image'] = $img_path . 'media/items/thumb350/' . $imageName;
					} else {
						$resultArray['image'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];
						$resultArray['image'] = $img_path . 'media/items/thumb350/' . $imageName;
					}
				}

			}
			if (!in_array($listitem['id'], $itemIds)) {
				$image = $img_path . 'media/items/thumb350/usrimg.jpg';
				list($width, $height) = getimagesize($image);
				$resultArray['image'] = $img_path . 'media/items/thumb350/usrimg.jpg';
				$resultArray['height'] = (!isset($height)) ? "" : $height;
				$resultArray['width'] = (!isset($width)) ? "" : $width;
			}

			return $resultArray;
	}
	function recentProducts($offset = null, $limit = null, $layout=null)
	{

		$this->loadModel('Items');
		$this->loadModel('Followers');
		$favitems_ids = array();
		$items_data = array();
		$tdy = strtotime(date("Y-m-d"));

		if ($layout == 'slider2') {
			$offset = 0;
			$limit = 20;
		} elseif(isset($offset)) {
			$limit = $limit;
			$offset = $offset;
		}else{
			$limit = 20;
			$offset = 0;
		}
		if (isset($offset)) {
			$items_data = $this->Items->find('all', array(
				'conditions' => array(
					'status' => 'publish'
				),
				'limit' => $limit,
				'offset' => $offset,
				'order' => 'Items.id DESC',
			))->contain('Forexrates');

		} else {
			$items_data = $this->Items->find('all', array(
				'conditions' => array(
					'status' => 'publish'
				),
				'limit' => $limit,
				'offset' => $offset,
				'order' => 'Items.id DESC',
			))->contain('Forexrates');
		}
		if (empty($items_data)) {
			echo '{"status":"false","message":"No data found"}';
			die;
		} else {
			$resultArray = $this->convertJsonHome($items_data, $favitems_ids, $_POST['user_id']);

			return $resultArray;
		}
	}

	function dailyDeals($offset = null, $limit = null)
	{

		$this->loadModel('Items');
		$this->loadModel('Followers');
		$favitems_ids = array();
		$items_data = array();

		$tdy = date("Y-m-d");

		if (isset($offset)) {
			$limit = $limit;
			$offset = $offset;
		} else {
			$limit = 10;
			$offset = 0;
		}
		if (isset($_POST['offset'])) {
			$items_data = $this->Items->find('all', array(
				'conditions' => array(
					'discount_type' => 'daily',
					'status' => 'publish',
					'dealdate' => $tdy
				),
				'limit' => $limit,
				'offset' => $_POST['offset'],

			))->contain('Forexrates');

		} else {
			$items_data = $this->Items->find('all', array(
				'conditions' => array(
					'discount_type' => 'daily',
					'status' => 'publish',
					'dealdate' => $tdy
				),
				'limit' => $limit,

			))->contain('Forexrates');
		}
		if (empty($items_data)) {
			echo '{"status":"false","message":"No data found"}';
			die;
		} else {
			$resultArray = $this->convertJsonHome($items_data, $favitems_ids, $_POST['user_id'], $_POST['user_id']);

			return json_encode($resultArray);
		}
	}

	function featuredProducts($offset = null, $limit = null, $layout=null)
	{

		$this->loadModel('Items');
		$this->loadModel('Followers');
		$favitems_ids = array();
		$items_data = array();

		if ($layout == 'slider2') {
			$offset = 0;
			$limit = 20;
		} elseif(isset($offset)) {
			$limit = $limit;
			$offset = $offset;
		}else{
			$limit = 20;
			$offset = 0;
		}

		//echo $offset.' - '.$limit; die;


		if (isset($offset)) {
			$items_data = $this->Items->find('all', array(
				'conditions' => array(
					'featured' => 1,
					'status' => 'publish'
				),
				'limit' => $limit,
				'offset' => $_POST['offset'],
				'order'=>'Items.id DESC'
			))->contain('Forexrates');

		} else {
			$items_data = $this->Items->find('all', array(
				'conditions' => array(
					'featured' => 1,
					'status' => 'publish'
				),
				'offset' => $offset,
				'limit' => $limit,
				'order'=>'Items.id DESC'

			))->contain('Forexrates');
		}

		$resultArray = $this->convertJsonHome($items_data, $favitems_ids, $_POST['user_id']);

		if (empty($resultArray)) {
			echo '{"status":"false","message":"No data found"}';
			die;
		} else {
			return $resultArray;
		}
			
		
	}

	function popularStores()
	{

		$this->loadModel('Shops');
		$this->loadModel('Storefollowers');
		$this->loadModel('Sitesettings');
		$userId = $_POST['user_id'];

		$offset = 0;
		$limit = 10;
		if (isset($_POST['offset'])) {
			$offset = $_POST['offset'];
		}
		if (isset($_POST['limit'])) {
			$limit = $_POST['limit'];
		}
		$setngs = $this->Sitesettings->find()->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}

		if (isset($_POST['offset'])) {
			$shopsdet = $this->Shops->find('all', array(
				'conditions' => array('seller_status' => 1, 'item_count >' => '0', 'store_enable' => 'enable'),
				'limit' => $limit,
				'offset' => $_POST['offset'],
				'order' => 'follow_count DESC',
			));

		} else {
			$shopsdet = $this->Shops->find('all', array(
				'conditions' => array('seller_status' => 1, 'item_count >' => '0', 'store_enable' => 'enable'),
				'limit' => $limit,
				'order' => 'follow_count DESC',
			));
		}



		foreach ($shopsdet as $key => $shops) {

			$profileimage = $shops['shop_image'];
			if (empty($profileimage)) {
				$profileimage = "usrimg.jpg";
			}
			$storeid = $shops['id'];

			$followers = $this->Storefollowers->find()->where(['store_id' => $storeid])->all();//all',array('conditions'=>array('Storefollower.store_id'=>$storeid)));
			$flwrusrids = array();
			foreach ($followers as $follower) {
				$flwrusrids[] = $follower['follow_user_id'];
			}
			$resultarray[$key]['store_id'] = $shops['id'];

			$resultarray[$key]['store_name'] = $shops['shop_name'];
			$resultarray[$key]['wifi'] = $shops['wifi'];
			$resultarray[$key]['merchant_name'] = $shops['merchant_name'];

			if (in_array($userId, $flwrusrids)) {
				$resultarray[$key]['status'] = 'unfollow';
			} else {
				$resultarray[$key]['status'] = 'follow';
			}
			$resultarray[$key]['image'] = $img_path . 'media/avatars/thumb150/' . $profileimage;
		}

		//echo '<pre>'; print_r($resultarray); die;
		if (!empty($resultarray) && !isset($_POST['offset'])) {
			return $resultarray;
		} elseif(!empty($resultarray) && isset($_POST['offset'])) {
			echo '{"status":"true","result":'.json_encode($resultarray).'}';	
		}else{
			echo '{"status":"false","message":"No stores found"}';	
		}
		
		die;

	}
	function banner()
	{
		$this->loadModel('Homepagesettings');
		$this->loadModel('Sitesettings');
		$this->loadModel('Categories');

		$setngs = $this->Sitesettings->find()->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}

		$homepageModel = $this->Homepagesettings->find()->where(['layout' => 'custom'])->first();
		$sliders = json_decode($homepageModel['slider'], true);

		$resultarray = array();
		$key = 0;
		
		
		foreach ($sliders as $slider) {

			if ($slider['mode'] == 'app') {

				if(isset($slider['type']) && $slider['type'] == 'categories')
				{
					$CategoryModel = $this->Categories->find()->where(['id' =>$slider['category']])->first();
					if($slider['subcategory'] != '0' || $slider['subcategory'] != '')
					{
						$subCategoryModel = $this->Categories->find()->where(['id' =>$slider['subcategory']])->first();
							$resultarray[$key]['subcategory_name'] = $subCategoryModel->category_name;
					}
					
					if($slider['supercategory'] != '0' || $slider['supercategory'] != '')
					{
						$superCategoryModel = $this->Categories->find()->where(['id' =>$slider['supercategory']])->first();
						$resultarray[$key]['supercategory_name'] = $superCategoryModel->category_name;
					}

					$resultarray[$key]['category_id'] = $slider['category'];
					$resultarray[$key]['category_name'] = $CategoryModel->category_name;
					$resultarray[$key]['subcategory_id'] = $slider['subcategory'];
					$resultarray[$key]['supercategory_id'] = $slider['supercategory'];
					$resultarray[$key]['type'] = $slider['type'];
					$resultarray[$key]['image'] = $img_path . 'images/slider/' . $slider['image'];
				}

				if(isset($slider['type']) && $slider['type'] == 'link')
				{
					$resultarray[$key]['slider_link'] = trim($slider['link']);	
					$resultarray[$key]['type'] = (!isset($slider['type'])) ? 'link' : $slider['type'];
						$resultarray[$key]['image'] = $img_path . 'images/slider/' . $slider['image'];
				}
				
				
				
				if(isset($slider['type']) && $slider['type'] == 'item')
				{
					$dfdfdf = $this->item_details($slider['content_id']);
					
					if(!empty($dfdfdf))
					{
						$resultarray[$key]['item_status'] = ($dfdfdf['item_status'] == 'publish') ? 'true' : 'false' ;
						$resultarray[$key]['product'] = array($dfdfdf);	
						$resultarray[$key]['type'] = (!isset($slider['type'])) ? 'link' : $slider['type'];
						$resultarray[$key]['image'] = $img_path . 'images/slider/' . $slider['image'];
					}	
				}
				$key++;
			}
		}
		return json_encode(array_values($resultarray));
	}

	function getItemss()
	{
		$this->loadModel('Items');
		$this->loadModel('Shops');

		$user_id = $_POST['user_id'];
		$search_key = $_POST['search_key'];
		$category_id = $_POST['category_id'];
		$subcat_id = $_POST['subcat_id'];
		$supercat_id = array();
		$supercat_id = $_POST['supercat_id'];
		if (!empty($_POST['supercat_id'])) {
			$supercat_id = explode(",", $_POST['supercat_id']);
			foreach ($supercat_id as $supercat_ids) {
				$supercat_id1[] = $supercat_ids;
			}

		}

		

		$colors = array();
		$colors = $_POST['color'];
		if (!empty($_POST['color'])) {
			$colors = explode(",", $_POST['color']);
			foreach ($colors as $colorss) {
				$color[] = $colorss;
			}
		}

		$price_min = $_POST['price_min'];
		$price_max = $_POST['price_max'];

		$distance = $_POST['distance'];
		$lat = $_POST['lat'];
		$lon = $_POST['lon'];
		$barcode = $_POST['barcode'];

		$sort = 'popularity';

		$offset = 0;
		$limit = 10;
		if (!empty($_POST['sort'])) {
			$sort = $_POST['sort'];
		}
		if (!empty($_POST['offset'])) {
			$offset = $_POST['offset'];
		}
		if (!empty($_POST['limit'])) {
			$limit = $_POST['limit'];
		}

		if (!empty($search_key) && empty($category_id) && empty($subcat_id) && empty($supercat1_id) && empty($price_min) && empty($price_max) && empty($colors) && empty($barcode)) {
			if (!empty($_POST['offset'])) {
				$items_data = $this->Items->find('all', array(
					'conditions' => array(

						'Items.item_title LIKE' => '%' . $search_key . '%'
					),
					'limit' => $limit,
					'offset' => $_POST['offset'],

				))->contain('Forexrates');

			} else {

				$items_data = $this->Items->find('all', array(
					'conditions' => array(

						'Items.item_title LIKE' => '%' . $search_key . '%'
					),
					'limit' => $limit,
				))->contain('Forexrates');
			}
		}

		if (!empty($barcode) && empty($category_id) && empty($subcat_id) && empty($supercat1_id) && empty($price_min) && empty($price_max) && empty($colors) && empty($search_key)) {
			if (!empty($_POST['offset'])) {
				$items_data = $this->Items->find('all', array(
					'conditions' => array(
						'Items.skuid LIKE' => '%' . $barcode . '%',

						'Items.item_title LIKE' => '%' . $search_key . '%'
					),
					'limit' => $limit,
					'offset' => $_POST['offset'],

				))->contain('Forexrates');

			} else {

				$items_data = $this->Items->find('all', array(
					'conditions' => array(

						'Items.skuid LIKE' => '%' . $barcode . '%',
						'Items.item_title LIKE' => '%' . $search_key . '%'
					),
					'limit' => $limit,
				))->contain('Forexrates');
			}
		}

		if (!empty($category_id) && empty($subcat_id) && empty($supercat1_id) && empty($price_min) && empty($price_max) && empty($colors) && empty($barcode) && empty($search_key)) {
			if (!empty($_POST['offset'])) {
				$items_data = $this->Items->find('all', array(
					'conditions' => array(
						'Items.category_id' => $category_id,

						'Items.item_title LIKE' => '%' . $search_key . '%'
					),
					'limit' => $limit,
					'offset' => $_POST['offset'],

				))->contain('Forexrates');

			} else {

				$items_data = $this->Items->find('all', array(
					'conditions' => array(
						'Items.category_id' => $category_id,

						'Items.item_title LIKE' => '%' . $search_key . '%'
					),
					'limit' => $limit,
				))->contain('Forexrates');
			}
		}

		if (!empty($category_id) && !empty($subcat_id) && empty($supercat1_id) && empty($price_min) && empty($price_max) && empty($colors) && empty($barcode) && empty($search_key)) {
			if (!empty($_POST['offset'])) {
				$items_data = $this->Items->find('all', array(
					'conditions' => array(
						'Items.category_id' => $category_id,
						'Items.super_catid' => $subcat_id,

						'Items.item_title LIKE' => '%' . $search_key . '%'
					),
					'limit' => $limit,
					'offset' => $_POST['offset'],

				))->contain('Forexrates');

			} else {

				$items_data = $this->Items->find('all', array(
					'conditions' => array(
						'Items.category_id' => $category_id,
						'Items.super_catid' => $subcat_id,

						'Items.item_title LIKE' => '%' . $search_key . '%'
					),
					'limit' => $limit,
				))->contain('Forexrates');
			}
		}
		if (!empty($category_id) && !empty($subcat_id) && !empty($supercat_id) && empty($price_min) && empty($price_max) && empty($colors) && empty($barcode) && empty($search_key)) {

			if (!empty($_POST['offset'])) {

				$items_data = $this->Items->find('all', array(
					'conditions' => array(
						'Items.category_id' => $category_id,
						'Items.super_catid' => $subcat_id,
						'Items.sub_catid IN' => $supercat_id1,

						'Items.item_title LIKE' => '%' . $search_key . '%'
					),
					'limit' => $limit,
					'offset' => $_POST['offset'],

				))->contain('Forexrates');

			} else {

				$items_data = $this->Items->find('all', array(
					'conditions' => array(
						'Items.category_id' => $category_id,
						'Items.super_catid' => $subcat_id,
						'Items.sub_catid IN' => $supercat_id1,

						'Items.item_title LIKE' => '%' . $search_key . '%'
					),
					'limit' => $limit,
				))->contain('Forexrates');
			}
		}
		if (!empty($_POST['sort'])) {
			switch (urldecode($_POST['sort'])) {
				case "popularity":
				$order = 'Items.fav_count DESC';
				break;
				case "hightolow":
				$order = 'Items.price DESC';
				break;
				case "lowtohigh":
				$order = 'Items.price ASC';
				break;
				case "newest":
				$order = 'Items.id DESC';
				break;
				default:
				$order = 'Items.id ASC';
			}
		} else {
			$order = 'Items.fav_count DESC';
		}
		if (!empty($category_id) && !empty($subcat_id) && !empty($supercat_id) && !empty($sort) && empty($price_min) && empty($price_max) && empty($colors) && empty($barcode) && empty($search_key)) {
			if (!empty($_POST['offset'])) {

				$items_data = $this->Items->find('all', array(
					'conditions' => array(
						'Items.category_id' => $category_id,
						'Items.super_catid' => $subcat_id,
						'Items.sub_catid IN' => $supercat_id1,

						'Items.item_title LIKE' => '%' . $search_key . '%'
					),
					'limit' => $limit,
					'offset' => $_POST['offset'],
					'order' => $order,

				))->contain('Forexrates');

			} else {

				$items_data = $this->Items->find('all', array(
					'conditions' => array(
						'Items.category_id' => $category_id,
						'Items.super_catid' => $subcat_id,
						'Items.sub_catid IN' => $supercat_id1,

						'Items.item_title LIKE' => '%' . $search_key . '%'
					),
					'limit' => $limit,
					'order' => $order,
				))->contain('Forexrates');
			}
		}

		if (!empty($category_id) && empty($subcat_id) && empty($supercat_id) && !empty($sort) && !empty($price_min) && !empty($price_max) && !empty($color) && !empty($affliate_only) && !empty($buy_only)) {
			if (!empty($distance)) {
				$distance = $distance * 0.1 / 11;
			} else {
				$distance = 25 * 0.1 / 11;
			}
			$Distance = $distance; // Range in degrees (0.1 degrees is close to 11km)
			$latN = 0;
			$latS = 0;
			$LonE = 0;
			$LonW = 0;
			$LatN = $lat + $Distance;
			$LatS = $lat - $Distance;
			$LonE = $lon + $Distance;
			$LonW = $lon - $Distance;

			$nearme = $this->Shops->find()->where(['shop_latitude >' => $LatS, ['shop_latitude <' => $LatN]])->andWhere(['shop_longitude >' => $LonW, ['shop_longitude <' => $LonE]])->toArray();

			$itemid = array();
			if (count($nearme) != 0) {
				foreach ($nearme as $n) {

					$itemid[] = $n['id'];

				}
			}

			foreach ($color as $key => $name) {
				if ($key == 0)
					$condition[] = "item_color like '%" . $_POST['color'] . "%'";
				else
					$condition[] = "or item_color like '%" . $_POST['color'] . "%'";
				$similarcolor = $this->Items->find()->where([$condition])->all();
				foreach ($similarcolor as $similarcolors) {

					$itemids[] = $similarcolors['id'];
					$shopuserids[] = $similarcolors['user_id'];

				}
			}

			if (count($itemid) == 0) {
				$shop_data = $this->Shops->find()->where(['user_id IN' => $shopuserids])->all();
				foreach ($shop_data as $shop_datas) {
					$itemid[] = $shop_datas['id'];
				}
			}

			if (count($itemids) != 0) {
				if (!empty($_POST['offset'])) {
					$items_data = $this->Items->find('all', array(
						'conditions' => array(
							'Items.id IN' => $itemids,
							'Items.category_id' => $category_id,
							'Items.shop_id IN' => $itemid,
							'Items.item_title LIKE' => '%' . $search_key . '%',
							'Items.price >=' => $price_min,
							'Items.price <=' => $price_max,
							'Items.status' => $status,
						),
						'limit' => $limit,
						'offset' => $_POST['offset'],
						'order' => $order,
					))->contain('Forexrates');

				} else {

					$items_data = $this->Items->find('all', array(
						'conditions' => array(
							'Items.id IN' => $itemids,
							'Items.category_id' => $category_id,
							'Items.shop_id IN' => $itemid,
							'Items.item_title LIKE' => '%' . $search_key . '%',
							'Items.price >=' => $price_min,
							'Items.price <=' => $price_max,
							'Items.status' => $status,
						),
						'limit' => $limit,
						'order' => $order,
					))->contain('Forexrates');
				}
			} else {
				$items_data = [];
			}

		}
		if (!empty($category_id) && !empty($subcat_id) && empty($supercat_id) && !empty($sort) && !empty($price_min) && !empty($price_max) && !empty($color) && !empty($affliate_only) && !empty($buy_only)) {

			if (!empty($distance)) {
				$distance = $distance * 0.1 / 11;
			} else {
				$distance = 25 * 0.1 / 11;
			}
			$Distance = $distance; // Range in degrees (0.1 degrees is close to 11km)
			$latN = 0;
			$latS = 0;
			$LonE = 0;
			$LonW = 0;
			$LatN = $lat + $Distance;
			$LatS = $lat - $Distance;
			$LonE = $lon + $Distance;
			$LonW = $lon - $Distance;

			$nearme = $this->Shops->find()->where(['shop_latitude >' => $LatS, ['shop_latitude <' => $LatN]])->andWhere(['shop_longitude >' => $LonW, ['shop_longitude <' => $LonE]])->toArray();

			$itemid = array();
			if (count($nearme) != 0) {
				foreach ($nearme as $n) {

					$itemid[] = $n['id'];

				}
			}

			foreach ($color as $name) {
				$condition = array('item_color LIKE' => '%' . $name . '%');
				$similarcolor = $this->Items->find()->where([$condition])->all();
				foreach ($similarcolor as $similarcolors) {

					$itemids[] = $similarcolors['id'];
					$shopuserids[] = $similarcolors['user_id'];

				}
			}

			if (count($itemid) == 0) {
				$shop_data = $this->Shops->find()->where(['user_id IN' => $shopuserids])->all();
				foreach ($shop_data as $shop_datas) {
					$itemid[] = $shop_datas['id'];
				}
			}

			if (count($itemids) != 0) {

				if (!empty($_POST['offset'])) {

					$items_data = $this->Items->find('all', array(
						'conditions' => array(
							'Items.id IN' => $itemids,
							'Items.category_id' => $category_id,
							'Items.super_catid' => $subcat_id,
							'Items.shop_id IN' => $itemid,
							'Items.item_title LIKE' => '%' . $search_key . '%',
							'Items.price >=' => $price_min,
							'Items.price <=' => $price_max,
							'Items.status' => $status,
						),
						'limit' => $limit,
						'offset' => $_POST['offset'],
						'order' => $order,
					))->contain('Forexrates');

				} else {

					$items_data = $this->Items->find('all', array(
						'conditions' => array(
							'Items.id IN' => $itemids,
							'Items.category_id' => $category_id,
							'Items.super_catid' => $subcat_id,
							'Items.shop_id IN' => $itemid,
							'Items.item_title LIKE' => '%' . $search_key . '%',
							'Items.price >=' => $price_min,
							'Items.price <=' => $price_max,
							'Items.status' => $status,
						),
						'limit' => $limit,
						'order' => $order,
					))->contain('Forexrates');
				}
			} else {
				$items_data = [];
			}

		}

		if (!empty($category_id) && !empty($subcat_id) && !empty($supercat_id) && !empty($sort) && !empty($price_min) && !empty($price_max) && !empty($color) && !empty($affliate_only) && !empty($buy_only)) {

			if (!empty($distance)) {
				$distance = $distance * 0.1 / 11;
			} else {
				$distance = 25 * 0.1 / 11;
			}
			$Distance = $distance; // Range in degrees (0.1 degrees is close to 11km)
			$latN = 0;
			$latS = 0;
			$LonE = 0;
			$LonW = 0;
			$LatN = $lat + $Distance;
			$LatS = $lat - $Distance;//10.100833
			$LonE = $lon + $Distance;//78.239136
			$LonW = $lon - $Distance;
			$nearme = $this->Shops->find()->where(['shop_latitude >' => $LatS, ['shop_latitude <' => $LatN]])->andWhere(['shop_longitude >' => $LonW, ['shop_longitude <' => $LonE]])->toArray();
			$itemid = array();
			foreach ($nearme as $n) {

				$itemid[] = $n['id'];

			}
			if (count($itemid) == 0) {

				echo '{"status":"false","message":"No data found"}';
				die;
			}

			foreach ($color as $name) {
				$condition = array('item_color LIKE' => '%' . $name . '%');
				$similarcolor = $this->Items->find()->where([$condition])->all();
				foreach ($similarcolor as $similarcolors) {

					$itemids[] = $similarcolors['id'];

				}
			}

			if (count($itemid) != 0 && count($itemids) != 0) {

				if (!empty($_POST['offset'])) {

					$items_data = $this->Items->find('all', array(
						'conditions' => array(
							'Items.id IN' => $itemids,
							'Items.category_id' => $category_id,
							'Items.super_catid' => $subcat_id,
							'Items.sub_catid IN' => $supercat_id1,
							'Items.shop_id IN' => $itemid,
							'Items.item_title LIKE' => '%' . $search_key . '%',
							'Items.price >=' => $price_min,
							'Items.price <=' => $price_max,
							'Items.status' => $status,
						),
						'limit' => $limit,
						'offset' => $_POST['offset'],
						'order' => $order,
					))->contain('Forexrates');

				} else {
					$items_data = $this->Items->find('all', array(
						'conditions' => array(
							'Items.id IN' => $itemids,
							'Items.category_id' => $category_id,
							'Items.super_catid' => $subcat_id,
							'Items.sub_catid IN' => $supercat_id1,
							'Items.shop_id IN' => $itemid,
							'Items.item_title LIKE' => '%' . $search_key . '%',
							'Items.price >=' => $price_min,
							'Items.price <=' => $price_max,
							'Items.status' => $status,
						),
						'limit' => $limit,
						'order' => $order,
					))->contain('Forexrates');
				}
			} else {
				$items_data = [];
			}

		}
		if (empty($category_id) && empty($subcat_id) && empty($supercat1_id) && empty($price_min) && empty($price_max) && empty($colors) && empty($barcode) && empty($search_key)) {
			$items_data = $this->Items->find('all', array('limit' => 10))->contain('Forexrates');
		}

		$resultArray = $this->convertJsonHome($items_data, $favitems_ids, $_POST['user_id']);
		if (count($resultArray) == 0) {
			echo '{"status":"false","message":"No data found"}';
			die;
		}
		echo '{"status":"true","items":' . json_encode($resultArray) . '}';
		die;

	}

	function getItems()
	{

		$itemstable = TableRegistry::get('Items');
		$itemliststable = TableRegistry::get('Itemlists');
		$colorstable = TableRegistry::get('Colors');
		$pricestable = TableRegistry::get('Prices');
		$bannerstable = TableRegistry::get('Banners');
		$categoriestable = TableRegistry::get('Categories');

		$this->loadModel('Prices');
		$this->loadModel('Colors');

		$sitesettingstable = TableRegistry::get('Sitesettings');
		$setngs = $sitesettingstable->find()->where(['id' => 1])->first();

		$banner_datas = $bannerstable->find()->where(['banner_type' => 'shop'])->first();
		$this->set('banner_datas', $banner_datas);

		$startIndex = 0;
		$offset = 10;
		if (!empty($_POST['offset'])) {
			$startIndex = $_POST['offset'];
		}
		if (!empty($_POST['limit'])) {
			$offset = $_POST['limit'];
		}

		$userid = $_POST['user_id'];

		$itemstable = TableRegistry::get('Items');
		$itemarray = (array)$itemstable;
		$array = json_decode(json_encode($itemarray), true);
		$itemtablename = [];
		foreach ($itemarray as $key => $value) {
			$itemtablename[] = $value;
		}
		$itemtable = $itemtablename[0];

		$forexratestable = TableRegistry::get('Forexrates');

		$forexratearray = (array)$forexratestable;
		$array = json_decode(json_encode($forexratearray), true);
		$forexratetablename = [];
		foreach ($forexratearray as $key => $value) {
			$forexratetablename[] = $value;
		}
		$forexratetable = $forexratetablename[0];

		$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();
		if (isset($userid))
			$userDetail = $this->Users->find()->where(['id' => $userid])->first();
		else
			$userDetail = "";

		$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();
		if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "" || count($userDetail) == 0) {
			$cur_symbol = $forexrateModel['currency_symbol'];
			$cur_value = $forexrateModel['price'];
		} else {
			$cur_symbol = $currency_value['currency_symbol'];
			$cur_value = $currency_value['price'];
		}

		$conn = ConnectionManager::get('default');
		$stmt = $conn->execute('select * from fc_items');

		$rows = $stmt->fetchAll('assoc');

		if ($_POST['sort'] == 'hightolow') {
			$orderby = 'order by (select (price/(select price from ' . $forexratetable . ' as f where f.id=i.currencyid))*' . $cur_value . ' as price) desc';
		} else if ($_POST['sort'] == 'lowtohigh') {
			$orderby = 'order by (select (price/(select price from ' . $forexratetable . ' as f where f.id=i.currencyid))*' . $cur_value . ' as price) asc';
		} else if ($_POST['sort'] == 'newest') {
			$orderby = "ORDER BY id DESC ";
		} else if ($_POST['sort'] == 'oldest') {
			$orderby = "ORDER BY id ASC ";
		} else if ($_POST['sort'] == 'popularity') {
			$orderby = "ORDER BY fav_count DESC ";
		} else {
			$orderby = "ORDER BY fav_count DESC ";
		}

		if (isset($_POST['category_id']) && $_POST['category_id'] != "" && $_POST['category_id'] != '0') {
			$catcondition = 'category_id=' . $_POST['category_id'];
			if (isset($_POST['subcat_id']) && $_POST['subcat_id'] != "" && $_POST['subcat_id'] != '0')
				$catcondition .= ' and super_catid=' . $_POST['subcat_id'];
			if (isset($_POST['supercat_id']) && $_POST['supercat_id'] != "" && $_POST['supercat_id'] != '0') {
				$supercat_id = explode(",", $_POST['supercat_id']);
				foreach ($supercat_id as $supercat_ids) {
					$supercat_id1[] = $supercat_ids;
				}
				$supercatids = join("','", $supercat_id1);
				$catcondition .= " and sub_catid IN ('$supercatids')";
			}
		} else {
			$catcondition = "";
		}

		if (isset($_POST['color']) && $_POST['color'] != "") {
			if (!empty($_POST['color'])) {
				$colors = explode(",", $_POST['color']);
				foreach ($colors as $colorss) {
					$color[] = $colorss;
				}
			}
			if ($catcondition == "") {
				foreach ($color as $key => $name) {
					if ($key == 0)
						$colorcondition = "item_color like '%" . $name . "%'";
					else
						$colorcondition .= "and item_color like '%" . $name . "%'";
				}
			} else {
				foreach ($color as $key => $name) {
					if ($key == 0)
						$colorcondition = "and item_color like '%" . $name . "%'";
					else
						$colorcondition .= "and item_color like '%" . $name . "%'";
				}
			}
		} else {
			$colorcondition = "";
		}
		if (isset($_POST['search_key']) && $_POST['search_key'] != "") {
			if ($colorcondition == "" && $catcondition == "")
				$searchcondition = "item_title like '%" . $_POST['search_key'] . "%'";
			else
				$searchcondition = "and item_title like '%" . $_POST['search_key'] . "%'";

		} else {
			$searchcondition = "";
		}

		if (isset($_POST['barcode']) && $_POST['barcode'] != "") {
			if ($colorcondition == "" && $catcondition == "" && $searchcondition == "")
				$barcodecondition = "skuid like '" . $_POST['barcode'] . "'";
			else
				$barcodecondition = "and skuid like '" . $_POST['barcode'] . "'";
		} else {
			$barcodecondition = "";
		}

		if (isset($_POST['lat']) && $_POST['lat'] != "" && isset($_POST['lon']) && $_POST['lon'] != "") {
			$lat = $_POST['lat'];
			$lon = $_POST['lon'];
			if (isset($_POST['distance']) && $_POST['distance'] != "") {
				$distance = $_POST['distance'];
				$distance = $distance * 0.1 / 11;
			} else {
				$distance = 25 * 0.1 / 11;
			}
        	//echo $distance;
			$Distance = $distance; // Range in degrees (0.1 degrees is close to 11km)
			$latN = 0;
			$latS = 0;
			$LonE = 0;
			$LonW = 0;
			$LatN = $lat + $Distance;
			$LatS = $lat - $Distance;
			$LonE = $lon + $Distance;
			$LonW = $lon - $Distance;

			$this->loadModel('Shops');
			$nearme = $this->Shops->find()->where(['shop_latitude >' => $LatS, ['shop_latitude <' => $LatN]])->andWhere(['shop_longitude >' => $LonW, ['shop_longitude <' => $LonE]])->toArray();

			$itemid = array();
			if (count($nearme) > 0) {
				foreach ($nearme as $n) {
					$itemid[] = $n['id'];
				}
			}
			if (count($itemid) > 0) {

				$itemids = join("','", $itemid);

				if ($colorcondition == "" && $catcondition == "" && $searchcondition == "" && $barcodecondition == "")
					$latloncondition = "shop_id IN ('$itemids')";
				else
					$latloncondition = "and shop_id IN ('$itemids')";
			} else {
				echo '{"status":"false","message":"No data found"}';
				die;
			}
		} else {
			$latloncondition = "";
		}

		if ($setngs['affiliate_enb'] == 'enable') {
			$publish_condition = "and status !='publish'";
		} else {
			$publish_condition = "and status ='publish'";
		}

		if (isset($_POST['category_id']) && $_POST['category_id'] != "" && $_POST['category_id'] != '0') {
			if (isset($_POST['price_min']) && $_POST['price_min'] != "" && isset($_POST['price_max']) && $_POST['price_max'] != "") {
                //$price = explode('-', $_POST['price']);
				$price1 = $_POST['price_min'];
				$price2 = $_POST['price_max'];
				$item_datas = $conn->execute('select * from ' . $itemtable . ' as i where (select (price/(select price from ' . $forexratetable . ' as f where f.id=i.currencyid))*' . $cur_value . ' as price) between ' . $price1 . ' and ' . $price2 . ' and ' . $catcondition . ' ' . $colorcondition . ' ' . $searchcondition . ' ' . $barcodecondition . ' ' . $latloncondition . ' ' .$publish_condition. ' ' .$orderby . ' limit ' . $startIndex . ',' . $offset . '');
				$rows = $item_datas->fetchAll('assoc');
				foreach ($rows as $key => $value) {
					$newarray[] = $value['id'];

				}
			} else {
				$item_datas = $conn->execute('select * from ' . $itemtable . ' as i where ' . $catcondition . ' ' . $colorcondition . ' ' . $searchcondition . ' ' . $barcodecondition . ' ' . $latloncondition . ' ' .$publish_condition. ' ' . $orderby . ' limit ' . $startIndex . ',' . $offset . '');
				$rows = $item_datas->fetchAll('assoc');
				foreach ($rows as $key => $value) {
					$newarray[] = $value['id'];

				}
			}

		} else {
			if (isset($_POST['price_min']) && $_POST['price_min'] != "" && isset($_POST['price_max']) && $_POST['price_max'] != "") {
                //$price = explode('-', $_POST['price']);
				$price1 = $_POST['price_min'];
				$price2 = $_POST['price_max'];
				if ($catcondition == "" && $colorcondition != "")
					$colorcondition = "and " . $colorcondition;
				if ($catcondition == "" && $colorcondition == "" && $searchcondition != "")
					$searchcondition = "and " . $searchcondition;
				if ($catcondition == "" && $colorcondition == "" && $searchcondition == "" && $barcodecondition != "")
					$barcodecondition = "and " . $barcodecondition;
				if ($catcondition == "" && $colorcondition == "" && $searchcondition == "" && $barcodecondition == "" && $latloncondition != "")
					$latloncondition = "and " . $latloncondition;
				$item_datas = $conn->execute('select * from ' . $itemtable . ' as i where (select (price/(select price from ' . $forexratetable . ' as f where f.id=i.currencyid))*' . $cur_value . ' as price) between ' . $price1 . ' and ' . $price2 . ' ' . $catcondition . ' ' . $colorcondition . ' ' . $searchcondition . ' ' . $barcodecondition . ' ' . $latloncondition . ' ' .$publish_condition. ' ' . $orderby . ' limit ' . $startIndex . ',' . $offset . '');
				$rows = $item_datas->fetchAll('assoc');
				foreach ($rows as $key => $value) {
					$newarray[] = $value['id'];

				}
			} else {
				if ($catcondition == "" && $colorcondition == "" && $searchcondition == "" && $barcodecondition == "" && $latloncondition == "") {
					$item_datas = $conn->execute('select * from ' . $itemtable . ' as i where status="publish"' . $orderby . ' limit ' . $startIndex . ',' . $offset . '');
				} else {
					$item_datas = $conn->execute('select * from ' . $itemtable . ' as i where ' . $catcondition . ' ' . $colorcondition . ' ' . $searchcondition . ' ' . $barcodecondition . ' ' . $latloncondition . ' ' .$publish_condition. ' ' . $orderby . ' limit ' . $startIndex . ',' . $offset . '');
				}
				$rows = $item_datas->fetchAll('assoc');
				foreach ($rows as $key => $value) {
					$newarray[] = $value['id'];

				}
			}

			
		}

		if ($setngs['affiliate_enb'] == 'enable') {
			$conditions['Items.status !='] = 'draft';
		} else {
			$conditions['Items.status'] = 'publish';
		}



		if (!empty($newarray)) {
			foreach ($newarray as $key => $value) {
				$item[] = $itemstable->find()->contain('Photos')->contain('Forexrates')->where([$conditions])->where(['Items.id' => $value])->where(['Items.affiliate_commission IS NULL'])->all();
			}
		} else {
			$item = "";
		}

		$newresultarray = [];
		$resultArray = [];
		if (isset($_POST['user_id']))
			$user_id = $_POST['user_id'];
		else
			$user_id = "";


		foreach ($item as $key => $items_data) {
			$resultArray[] = $this->convertJsonHome($items_data, $favitems_ids, $user_id);
		}
		
		foreach ($resultArray as $key => $value) {
			foreach ($value as $resultkey => $resultvalue) {
				$newresultarray[] = $resultvalue;
			}
		}

		if ($newresultarray == "") {
			echo '{"status":"false","message":"No data found"}';
			die;
		} else {
			echo '{"status":"true","items":' . json_encode($newresultarray) . '}';
			die;
		}

	}

	function getCategories()
	{

		$this->loadModel('Categories');
		$resultarray = array();

		$CategoryModel = $this->Categories->find()->where(['category_parent' => 0])->toArray();//all',array('conditions'=>array('category_parent'=>'0')));
		if (count($CategoryModel) > 0) {
			for ($i = 0; $i < count($CategoryModel); $i++) {
				$resultarray[$i] = array();
				$categoryId = $CategoryModel[$i]['id'];
				$resultarray[$i]['id'] = $categoryId;
				$resultarray[$i]['name'] = $CategoryModel[$i]['category_name'];
				$cat_image = $CategoryModel[$i]['category_webicon'];
						//$resultarray[$i]['icon'] = SITE_URL.'images/category/'.$cat_image;
				if ($cat_image == "") {
					$resultarray[$i]['icon'] = SITE_URL . 'media/avatars/thumb70/usrimg.jpg';
				} else {

						//$imageName = $photo['image_name'];

					$imageName = WWW_ROOT . 'images/category/' . $cat_image;

					if (file_exists($imageName)) {
						$resultarray[$i]['icon'] = SITE_URL . 'images/category/' . $cat_image;

					} else {
						$resultarray[$i]['icon'] = SITE_URL . 'media/avatars/thumb70/usrimg.jpg';
					}

				}

				$resultarray[$i]['sub_category'] = array();
				$subcategoryModel = $this->Categories->find()->where(['category_parent' => $categoryId])->andWhere(['category_sub_parent' => 0])->toArray();//$this->Category->find('all',array('conditions'=>array('category_parent'=>$categoryId,'category_sub_parent'=>'0')));
				if (count($subcategoryModel) > 0) {
					for ($j = 0; $j < count($subcategoryModel); $j++) {
						$subcatid = $subcategoryModel[$j]['id'];
						$subname = $subcategoryModel[$j]['category_name'];
						$resultarray[$i]['sub_category'][$j] = array();
						$resultarray[$i]['sub_category'][$j]['id'] = $subcatid;
						$resultarray[$i]['sub_category'][$j]['name'] = $subname;
						$resultarray[$i]['sub_category'][$j]['super_category'] = array();
						$subcatModel = $this->Categories->find()->where(['category_parent' => $categoryId])->andWhere(['category_sub_parent' => $subcatid])->toArray();
						if (count($subcatModel) > 0) {
							for ($k = 0; $k < count($subcatModel); $k++) {
								$resultarray[$i]['sub_category'][$j]['super_category'][$k] = array();
								$subsubid = $subcatModel[$k]['id'];
								$subsubname = $subcatModel[$k]['category_name'];
								$resultarray[$i]['sub_category'][$j]['super_category'][$k]['id'] = $subsubid;
								$resultarray[$i]['sub_category'][$j]['super_category'][$k]['name'] = $subsubname;
							}
						}
					}
				}
			}

		}
		if (!empty($resultarray)) {
					//$resultarray = json_encode($resultarray);
			$resultarray = json_encode($resultarray);
			echo '{"status":"true","category":' . $resultarray . '}';
			die;
		} else
		echo '{"status":"false","message":"No Category found"}';
		die;

	}

	function itemLike()
	{
		$this->loadModel('Itemfavs');
		$this->loadModel('Items');
		$this->loadModel('Users');
		$this->loadModel('Sitesettings');
		$this->loadModel('Photos');
		$setngs = $this->Sitesettings->find()->toArray();
		if (!empty($_POST)) {
			$userId = $_POST['user_id'];
			if ($this->disableusercheck($userId) == 0) {
				echo '{"status":"error","message":"The user has been blocked by admin"}';
				die;
			}
			$itemId = $_POST['item_id'];
			$ItemfavModel = $this->Itemfavs->find()->where(['user_id' => $userId])->andWhere(['item_id' => $itemId])->all();
			$userdatasall = $this->Items->find()->where(['id' => $itemId])->first();
			if (count($ItemfavModel) != 0) {
				$this->Itemfavs->deleteAll(array('user_id' => $userId, 'item_id' => $itemId), false);
				$favcountss = $userdatasall['fav_count'];
				$favcounts = $favcountss - 1;
				$userdatasall->id = $itemId;
				$userdatasall->fav_count = $favcounts;
				$this->Items->save($userdatasall);

				echo '{"status":"true","message":"Item unliked"}';
				die;
			} else {
				$favcountss = $userdatasall['fav_count'];
				$favcounts = $favcountss + 1;
				$userdatasall->id = $itemId;
				if ($favcountss <= 0) {
					$userdatasall->fav_count = 1;
				} else {
					$userdatasall->fav_count = $favcounts;
				}
				$this->Items->save($userdatasall);
				$item_fav = $this->Itemfavs->newEntity();
				$item_fav->user_id = $userId;
				$item_fav->item_id = $itemId;
				$item_fav->created_on = date('Y-m-d H:i:s');
				$lastid = $this->Itemfavs->save($item_fav);
				$lastinsertId = $lastid->id;
				if ($userId != $userdatasall['user_id']) {
					$this->loadModel('Userdevices');
					$this->loadModel('Users');
					$notifyto = $userdatasall['user_id'];
					$notificationSettings = $this->Users->find()->where(['id' => $notifyto])->first();
						//$notificationSettings = $this->Users->getnotifysettings($notifyto);
					$notificationSettings = json_decode($notificationSettings['push_notifications'], true);
					if ($notificationSettings['somone_likes_ur_item_push'] == 1 && $userId != $notifyto) {
						$loguser = $this->Users->find()->where(['id' => $userId])->toArray();//all',array('conditions'=>array('User.id'=>$userId)));
						$logusername = $loguser[0]['username'];
						$logusernameurl = $loguser[0]['username_url'];
						$itemname = $userdatasall['item_title'];
						$itemurl = $userdatasall['item_title_url'];
						$liked = $setngs[0]['liked_btn_cmnt'];
						$image['image'] = $loguser[0]['profile_image'];
						$image['link'] = SITE_URL . "people/" . $logusernameurl;
						$photo = $this->Photos->find()->where(['item_id' => $itemId])->toArray();
						$image['image'] = $photo[0]['image_name'];
						$image['link'] = SITE_URL . "listing/" . $itemId . "/" . $itemurl;
						$loguserimage = json_encode($image);
						$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logusername . "</a>";
						$productlink = "<a href='" . SITE_URL . "listing/" . $itemId . "/" . $itemurl . "'>" . $itemname . "</a>";
						$notifymsg = $loguserlink . " " . $liked . " -___-your product-___- " . $productlink;
						$logdetails = $this->addlog('favorite', $userId, $notifyto, $lastinsertId, $notifymsg, null, $loguserimage, $itemId);
					}
					$this->loadModel('Userdevices');
					$itemstable = TableRegistry::get('Items');
					$userstable = TableRegistry::get('Users');
					$userdevicestable = TableRegistry::get('Userdevices');
					$getuserIdd = $itemstable->find()->contain('Users')->where(['Items.id' => $itemId])->first();
					if ($getuserIdd['user']['id'] != $userId) {
						$usernamedetails = $userstable->find()->where(['id' => $userId])->first();
						$userddett = $userdevicestable->find()->where(['user_id' => $getuserIdd['id']])->all();

						$logusername = $usernamedetails['username'];
						$logfirstname = $usernamedetails['first_name'];
						$liked = $setngs['liked_btn_cmnt'];
						foreach ($userddett as $userdet) {
							$deviceTToken = $userdet['deviceToken'];
							$badge = $userdet['badge'];
							$badge += 1;
							$this->Userdevices->updateAll(array('badge' => $badge), array('deviceToken' => $deviceTToken));
							if (isset($deviceTToken)) {
								$messages = $logfirstname . " " . $liked . " your item " . $getuserIdd['item_title'];
			                        //$this->pushnot($deviceTToken,$messages,$badge);
							}
						}
					}
				}
				echo '{"status":"true","message":"Item liked"}';
				die;
			}

		} else
		echo '{"status": "false", "message": "Something went to be wrong,Please try again later."}';
		die;
	}

	function login()
	{
		$this->loadModel('Sitesettings');
		$this->loadModel('Users');
		$email = $_POST['email'];
		$password = $_POST['password'];
		$setngs = $this->Sitesettings->find()->toArray();

		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}

		if ((!empty($email)) && (!empty($password))) {

		//$pass = $this->Auth->password($password);

			$userExist = $this->Users->find()->where(['email' => $email])->count();//count',array('conditions'=>array('email'=>$email)));

			$admin_data = $this->Users->find()->where(['email' => $email])->toArray();

			if ($userExist == '0') {
				echo '{"status":"false","message":"Please enter correct email and password"}';
				die;
			}

			if ($this->disableusercheck($admin_data[0]['id']) == 0) {
				echo '{"status":"error","message":"The user has been blocked by admin"}';
				die;
			}

			if ($admin_data[0]['user_level'] == 'god') {
				echo '{"status":"false","message":"You cannot login as Admin"}';
				die;
				return;
			}
			if ($admin_data[0]['user_level'] == 'moderator') {
				echo '{"status":"false","message":"You cannot login as Moderator"}';
				die;
				return;
			}
			if ($admin_data[0]['user_level'] == 'shop') {
				echo '{"status":"false","message":"You cannot login with merchant account"}';
				die;
				return;
			}

			$userdata = $this->Auth->identify();
			if ($userdata) {
				$this->Auth->setUser($userdata);

			}

			if (!empty($userdata)) {
			//echo strtotime($userdata['last_login']); die;

				if (strtotime($userdata['last_login']) == "")
					$first_time_logged = "yes";
				else
					$first_time_logged = "no";

				if ($userdata['activation'] == 1) {
					if ($userdata['user_status'] == 'enable') {

						$last_login = date('Y-m-d H:i:s');

						$this->Users->updateAll(array('last_login' => $last_login), array('id' => $userdata['id']));

						$userId = $userdata['id'];
						$userName = $userdata['username'];
						$fullname = $userdata['first_name'] . " " . $userdata['last_name'];
						$imageName = $userdata['profile_image'];

						$flag = 0;
						$pos = strpos($deviceToken, "aaa");
						if ($pos > 0) {
							$flag = 1;
						} elseif ($deviceToken == 'aaa') {
							$flag = 1;
						} else {
							$flag = 0;
						}

						if (!empty($deviceToken) && $flag != 1) {
							/*$mode = 0;
							if(isset($_POST['devicemode'])){
								$mode = 1;
							}*/
							$mode = $_POST['devicemode'];
							$this->loadModel('Userdevices');
							$userdeviceDet = $this->Userdevices->find()->where(['deviceToken' => $deviceToken])->toArray();//all',array('conditions'=>array('deviceToken'=>$deviceToken)));

							if (!empty($userdeviceDet)) {
								$devicetokentab = $userdeviceDet[0]['deviceToken'];
								if (!empty($_POST['devicetype'])) {
									$this->Userdevices->updateAll(array('Userdevice.user_id' => $userId, 'Userdevice.type' => $_POST['devicetype'], 'Userdevice.mode' => $mode), array('Userdevice.deviceToken' => $devicetokentab));
								} else {
									$this->Userdevices->updateAll(array('Userdevice.user_id' => $userId, 'Userdevice.mode' => $mode), array('Userdevice.deviceToken' => $devicetokentab));
								}
							} else {
								$device_data = $this->Userdevices->newEntity();
								$device_data->user_id = $userId;
								$device_data->deviceToken = $deviceToken;
								$device_data->mode = $mode;
								if (!empty($_POST['devicetype'])) {
									$device_data->type = $_POST['devicetype'];
								}
								$device_data->cdate = time();
								$this->Userdevices->save($device_data);
							}
						}

						if ($imageName == '') {
							$imageName = "usrimg.jpg";
						}
						$fullImageName = $img_path . 'media/avatars/thumb150/' . $imageName;
						echo '{"status":"true","user_id":"' . $userId . '","user_name":"' . $userName . '","user_image":"' . $fullImageName . '","full_name":"' . $fullname . '","first_time_logged":"' . $first_time_logged . '"}';
						die;

					} else {
						echo '{"status":"false","message":"Your account has been disbled please contact our support"}';
						die;
					}
				} else {
					echo '{"status":"false","message":"Please activate your account by the email sent to you"}';
					die;
				}
			} else {
				echo '{"status":"false","message":"Please enter correct email and password"}';
				die;
			}
		}
	}

function phonelogin(){

		$this->loadModel('Sitesettings');
		$this->loadModel('Users');
		$phone = $_POST['phone'];
		$phone = preg_replace('/[^0-9]/','',$phone);
		//echo $phone;die;
		//$password = $_POST['password'];
		$setngs = $this->Sitesettings->find()->toArray();

		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}
		if (!empty($phone)) {

		//$pass = $this->Auth->password($password);

			$userExist = $this->Users->find()->where(['phone_no' => $phone])->count();//count',array('conditions'=>array('email'=>$email)));

			$admin_data = $this->Users->find()->where(['phone_no' => $phone])->toArray();
			if ($userExist == '0') {
				echo '{"status":"false","message":"Please enter correct Phone number"}';
				die;
			}

			if ($this->disableusercheck($admin_data[0]['id']) == 0) {
				echo '{"status":"error","message":"The user has been blocked by admin"}';
				die;
			}

			if ($admin_data[0]['user_level'] == 'god') {
				echo '{"status":"false","message":"You cannot login as Admin"}';
				die;
				return;
			}
			if ($admin_data[0]['user_level'] == 'moderator') {
				echo '{"status":"false","message":"You cannot login as Moderator"}';
				die;
				return;
			}
			if ($admin_data[0]['user_level'] == 'shop') {
				echo '{"status":"false","message":"You cannot login with merchant account"}';
				die;
				return;
			}

			if (!empty($admin_data)) {
			//echo '<pre>';print_r($admin_data[0]); die;

				if (strtotime($admin_data[0]['last_login']) == "")
					$first_time_logged = "yes";
				else
					$first_time_logged = "no";

				if ($admin_data[0]['activation'] == 1) {
					if ($admin_data[0]['user_status'] == 'enable') {

						$last_login = date('Y-m-d H:i:s');

						$this->Users->updateAll(array('last_login' => $last_login), array('id' => $admin_data[0]['id']));

						$userId = $admin_data[0]['id'];
						$userName = $admin_data[0]['username'];
						$fullname = $admin_data[0]['first_name'] . " " . $admin_data[0]['last_name'];
						$imageName = $admin_data[0]['profile_image'];

						$flag = 0;
						$pos = strpos($deviceToken, "aaa");
						if ($pos > 0) {
							$flag = 1;
						} elseif ($deviceToken == 'aaa') {
							$flag = 1;
						} else {
							$flag = 0;
						}

						if (!empty($deviceToken) && $flag != 1) {
							/*$mode = 0;
							if(isset($_POST['devicemode'])){
								$mode = 1;
							}*/
							$mode = $_POST['devicemode'];
							$this->loadModel('Userdevices');
							$userdeviceDet = $this->Userdevices->find()->where(['deviceToken' => $deviceToken])->toArray();//all',array('conditions'=>array('deviceToken'=>$deviceToken)));

							if (!empty($userdeviceDet)) {
								$devicetokentab = $userdeviceDet[0]['deviceToken'];
								if (!empty($_POST['devicetype'])) {
									$this->Userdevices->updateAll(array('Userdevice.user_id' => $userId, 'Userdevice.type' => $_POST['devicetype'], 'Userdevice.mode' => $mode), array('Userdevice.deviceToken' => $devicetokentab));
								} else {
									$this->Userdevices->updateAll(array('Userdevice.user_id' => $userId, 'Userdevice.mode' => $mode), array('Userdevice.deviceToken' => $devicetokentab));
								}
							} else {
								$device_data = $this->Userdevices->newEntity();
								$device_data->user_id = $userId;
								$device_data->deviceToken = $deviceToken;
								$device_data->mode = $mode;
								if (!empty($_POST['devicetype'])) {
									$device_data->type = $_POST['devicetype'];
								}
								$device_data->cdate = time();
								$this->Userdevices->save($device_data);
							}
						}

						if ($imageName == '') {
							$imageName = "usrimg.jpg";
						}
						$fullImageName = $img_path . 'media/avatars/thumb150/' . $imageName;
						echo '{"status":"true","user_id":"' . $userId . '","email":"' . $admin_data[0]['email'] . '","user_name":"' . $userName . '","user_image":"' . $fullImageName . '","full_name":"' . $fullname . '","first_time_logged":"' . $first_time_logged . '"}';
						die;

					} else {
						echo '{"status":"false","message":"Your account has been disbled please contact our support"}';
						die;
					}
				} else {
					echo '{"status":"false","message":"Please activate your account by the email sent to you"}';
					die;
				}
			} else {
				echo '{"status":"false","message":"Please enter correct Phone number"}';
				die;
			}
		}
	}

	function changePassword()
	{

		$this->loadModel('Users');
		$loguserid = $_POST['user_id'];
		if ($this->disableusercheck($loguserid) == 0) {
			echo '{"status":"error","message":"The user has been blocked by admin"}';
			die;
		}
		$usr_datas = $this->Users->find()->where(['id' => $loguserid])->first();
		if (!empty($_POST) && count($_POST) > 0) {
			$exispassword = (new DefaultPasswordHasher)->hash($_POST['old_password']);
			$newpassword = $_POST['new_password'];
			$apass = (new DefaultPasswordHasher)->hash($newpassword);
			$verify = (new DefaultPasswordHasher)->check($_POST['old_password'], $usr_datas['password']);

			if ($verify == 1) {
				$this->Users->updateAll(array('password' => $apass), array('id' => $usr_datas['id']));
				echo '{"status":"true","message":"Password Changed Successfully"}';
				die;

			} elseif ($usr_datas['password'] == "" && $_POST['old_password'] == "") {
				$this->Users->updateAll(array('password' => $apass), array('id' => $usr_datas['id']));
				echo '{"status":"true","message":"Password Changed Successfully"}';
				die;

			} else {
				echo '{"status":"false","message":"Old Password Incorrect"}';
				die;
			}

		}
	}

	function signup()
	{
		$this->loadModel('Users');
		$this->loadModel('Sitesettings');
		$setngs = $this->Sitesettings->find()->toArray();
			//if(!empty($_POST['user_name']) && !empty($_POST['full_name']) && !empty($_POST['email']) && !empty($_POST['password'])){
		$username = $_POST['user_name'];
		$firstname = $_POST['full_name'];
		$email = $_POST['email'];
		$password = $_POST['password'];
		// $phone_no = $_POST['phone'];
		// $phone_no = preg_replace('/[^0-9]/','',$phone_no);
		// if(!empty($phone_no)) {
		// 	$phonecounts = $this->Users->find()->where(['phone_no' => $phone_no])->count();

		// 	if ($phonecounts > 0) {
		// 		echo '{"status":"false","message":"Phone number already exists"}';
		// 		die;
		// 	}
		// 	//$deviceToken = $_POST['deviceToken'];
		// } else {
		// 	echo '{"status":"false","message":"Enter your phone number"}';
		// 	die;
		// }
		$nmecounts = $this->Users->find()->where(['username' => $username])->count();//count',array('conditions'=>array('username'=>$username)));
		$emlcounts = $this->Users->find()->where(['email' => $email])->count();
		if ($nmecounts > 0) {
			echo '{"status":"false","message":"Username already exists"}';
			die;
		} else if ($emlcounts > 0) {
			echo '{"status":"false","message":"Email already exists"}';
			die;
		} else {
			$addmember = $this->Users->newEntity();
			$name = $addmember->username = $username;
			$urlname = $addmember->username_url = $this->Urlfriendly->utils_makeUrlFriendly($username);
			$addmember->first_name = $firstname;
			$emailaddress = $addmember->email = $email;
			$addmember->password = (new DefaultPasswordHasher)->hash($this->request->data('password'));
			$addmember->user_level = 'normal';
					//if ($setngs[0]['signup_active'] == 'no') {
			$addmember->activation = 1;
			//$addmember->phone_no = $phone_no;
			$addmember->credit_points = $setngs[0]['signup_credit'];
			$addmember->user_status = 'enable';
					//}else{
						//$addmember->user_status = 'disable';
					//}
			$addmember->push_notifications = '{"somone_flw_push":"1",
			"somone_cmnts_push":"1","somone_mentions_push":"1","somone_likes_ur_item_push":"1",
			"frends_flw_push":0,"frends_cmnts_push":0}';

			$addmember->created_at = date('Y-m-d H:i:s');
					//$uniquecode = $this->Urlfriendly->get_uniquecode(8);
					//$refer_key=$addmember->refer_key = $uniquecode;

			//print_r($addmember);die;
			/* Save new user */
			$result = $this->Users->save($addmember);
			//print_r($result);die;
			$userid = $result->id;

			$this->loadModel('Shops');
			$addshop = $this->Shops->newEntity();
			$addshop->user_id = $userid;
			$addshop->seller_status = 2;
			$this->Shops->save($addshop);

			$flag = 0;
			$pos = strpos($deviceToken, "aaa");
			if ($pos > 0) {
				$flag = 1;
			} elseif ($deviceToken == 'aaa') {
				$flag = 1;
			} else {
				$flag = 0;
			}

			if (!empty($deviceToken) && $flag != 1) {
						/*$mode = 0;
						if(isset($_POST['devicemode'])){
							$mode = 1;
						}*/
						$mode = $_POST['devicemode'];
						$this->loadModel('Userdevices');
				$userdeviceDet = $this->Userdevices->find()->where(['deviceToken' => $deviceToken])->toArray();//all',array('conditions'=>array('deviceToken'=>$deviceToken)));

				if (!empty($userdeviceDet)) {
					$devicetokentab = $userdeviceDet[0]['deviceToken'];
					if (!empty($_POST['devicetype'])) {
						$this->Userdevices->updateAll(array('Userdevice.user_id' => $userid, 'Userdevice.type' => $_POST['devicetype'], 'Userdevice.mode' => $mode), array('Userdevice.deviceToken' => $devicetokentab));
					} else {
						$this->Userdevices->updateAll(array('Userdevice.user_id' => $userid, 'Userdevice.mode' => $mode), array('Userdevice.deviceToken' => $devicetokentab));
					}
				} else {
					$device_data = $this->Userdevices->newEntity();
					$device_data->user_id = $userid;
					$device_data->deviceToken = $deviceToken;
					$device_data->mode = $mode;
					if (!empty($_POST['devicetype'])) {
						$device_data->type = $_POST['devicetype'];
					}
					$device_data->cdate = time();
					$this->Userdevices->save($device_data);
				}
			}
			if ($setngs[0]['signup_active'] == 'yes' && empty($phone_no)) {

				$activationquery = TableRegistry::get('Users')->query();
				$activationquery->update()->set(['activation' => '0'])
				->where(['id' => $userid])->execute();

				$subject = $setngs[0]['site_name'] . " – Welcome, please verify your new account";
				$template = 'userlogin';
				$emailid = base64_encode($emailaddress);
				$pass = base64_encode($password);
				$setdata = array('access_url' => SITE_URL . "verification/" . $emailid . "~" . $refer_key . "~" . $pass, 'name' => $firstname, 'urlname' => $urlname, 'email' => $emailaddress, 'siteurl' => SITE_URL, 'setngs' => $setngs);
				$this->sendmail($emailaddress, $subject, $messages, $template, $setdata);
				echo '{"status":"true","message":"An email was sent to your mail box, please activate your account and login."}';
				die;
			} else {
				echo '{"status":"true","message":"Your account has been created, please login to your account."}';
				die;
			}
		}
	}

	function viewSellermessage()
	{

		$this->loadModel('Users');
		$this->loadModel('Contactsellers');
		$this->loadModel('Contactsellermsgs');
		$this->loadModel('Photos');
		$this->loadModel('Items');
		$this->loadModel('Sitequeries');
		$this->loadModel('Shops');
		$this->loadModel('Sitesettings');

		$offset = 0;
		$limit = 10;
		if (isset($_POST['offset'])) {
			$offset = $_POST['offset'];
		}
		if (isset($_POST['limit'])) {
			$limit = $_POST['limit'];
		}
		$setngs = $this->Sitesettings->find()->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}

		if ($_POST['chat_id'] != "") {
			$contactsellerid = $_POST['chat_id'];
			$contact_seller_datas = $this->Contactsellers->find()->where(['id' => $contactsellerid])->toArray();//all',array('conditions'=>array('buyerid'=>$userid,'merchantid'=>$sellerid,'itemid'=>$itemid)));

			$shop_data = $this->Shops->find()->where(['user_id' => $contact_seller_datas[0]['merchantid']])->first();
			$itemid = $contact_seller_datas[0]['itemid'];
		} else {

			$userid = $_POST['user_id'];

			$shopid = $_POST['shop_id'];
			$itemid = $_POST['item_id'];
			$shop_data = $this->Shops->find()->where(['id' => $shopid])->first();

			$sellerid = $shop_data['user_id'];
			$contact_seller_datas = $this->Contactsellers->find()->where(['buyerid' => $userid, ['merchantid' => $sellerid]])->andWhere(['itemid' => $itemid])->toArray();//all',array('conditions'=>array('buyerid'=>$userid,'merchantid'=>$sellerid,'itemid'=>$itemid)));

			$contactsellerid = $contact_seller_datas[0]['id'];

		}
		if (count($contact_seller_datas) != 0) {
			$contact_seller_datas[0]->buyerread = 0;
			$this->Contactsellers->save($contact_seller_datas[0]);
		}
		$sellerid = $shop_data['user_id'];
		$shopprofileimage = $shop_data['shop_image']; //echo $profileimage;die;
		if (empty($shopprofileimage)) {
			$shopprofileimage = "usrimg.jpg";
		}
		$item_data = $this->Photos->find()->where(['item_id' => $itemid])->first();
		$itemimage = $item_data['image_name'];
		if (empty($itemimage)) {
			$itemimage = "usrimg.jpg";
		}

		$resultarray = array();
		$resultarray['chat_id'] = $contact_seller_datas[0]['id'];
		$resultarray['item_title'] = $contact_seller_datas[0]['itemname'];
		$resultarray['item_id'] = $contact_seller_datas[0]['itemid'];
		$resultarray['image'] = $img_path . 'media/items/thumb350/' . $itemimage;
		$resultarray['subject'] = $contact_seller_datas[0]['subject'];
		$resultarray['shop_id'] = $shop_data['id'];
		$resultarray['shop_name'] = $shop_data['shop_name'];
		$resultarray['shop_image'] = $img_path . 'media/avatars/thumb150/' . $shopprofileimage;
		if ($_POST['offset'] != 0) {
			$messages = $this->Contactsellermsgs->find('all', array(
				'conditions' => array(
					'contactsellerid' => $contactsellerid
				),
				'limit' => $limit,
				'offset' => $_POST['offset'],
				'order' => 'id DESC',

			));

		} else {

			$messages = $this->Contactsellermsgs->find('all', array(
				'conditions' => array(
					'contactsellerid' => $contactsellerid
				),
				'limit' => $limit,
				'order' => 'id DESC',

			));
		}

			// $messages= $this->Contactsellermsgs->find()->where(['contactsellerid' => $contactsellerid])->order(['id '=>'DESC'])->all();//all', array(

		foreach ($messages as $key => $message) {
			$contactsellerdatas = $this->Contactsellers->find()->where(['id' => $contactsellerid])->first();//ById($message['contactsellerid'])->first();
			$resultarray['messages'][$key] = array();
			$resultarray['messages'][$key]['message'] = $message['message'];
			if ($message['sentby'] == "buyer") {
				$user_datas = $this->Users->find()->where(['id' => $contactsellerdatas['buyerid']])->first();//Byid($contactsellerdatas['buyerid'])->first();

				$profileimage = $user_datas['profile_image'];
				if ($profileimage == "")
					$profileimage = "usrimg.jpg";

				$resultarray['messages'][$key]['user_name'] = $contactsellerdatas['buyername'];
				$resultarray['messages'][$key]['user_id'] = $contactsellerdatas['buyerid'];
				$resultarray['messages'][$key]['user_image'] = SITE_URL . 'media/avatars/thumb70/' . $profileimage;
			} else if ($message['sentby'] == "seller") {
				$user_datas = $this->Users->find()->where(['id' => $contactsellerdatas['merchantid']])->first();//Byid($contactsellerdatas['merchantid'])->first();

				$profileimage = $user_datas['profile_image'];
				if ($profileimage == "")
					$profileimage = "usrimg.jpg";
				$resultarray['messages'][$key]['user_name'] = $contactsellerdatas['sellername'];
				$resultarray['messages'][$key]['user_id'] = $contactsellerdatas['merchantid'];
				$resultarray['messages'][$key]['user_image'] = SITE_URL . 'media/avatars/thumb70/' . $profileimage;
			}
			$created_date = $message['createdat'];
			$resultarray['messages'][$key]['chat_date'] = $created_date;
		}

		if ($resultarray['chat_id'] != "") {
			$resultarray = json_encode($resultarray);

			echo '{"status":"true","result":' . $resultarray . '}';
			die;
		} else {
			$resultArray1 = array();
			$sitequeriesModel = $this->Sitequeries->find()->where(['type' => 'contact_seller'])->first();//first',array('conditions'=>array('type'=>'contact_seller')));
			$csqueries = json_decode($sitequeriesModel['queries'], true);
			if (!empty($csqueries)) {
				for ($s = 0; $s < count($csqueries); $s++) {
					$resultArray1[$s]['subject'] = $csqueries[$s];
				}
			} else {
				$resultArray1[0]['subject'] = "";
			}
			$resultarray1 = json_encode($resultArray1);
			echo '{"status":"false","result":' . $resultarray1 . '}';
			die;
		}

	}
	/* Follow User */
	function followUser()
	{
		$logusrid = $_POST['user_id'];
		$userid = $_POST['follow_id'];
		$this->loadModel('Followers');
		$this->loadModel('Shops');
		$this->loadModel('Users');
		$this->loadModel('Userdevices');
		$flwalrdy = $this->Followers->find()->where(['user_id' => $userid])->andWhere(['follow_user_id' => $logusrid])->count();
		if ($flwalrdy > 0) {
			echo '{"status":"false","message":"User Already Following"}';
			die;
		} else {
			$follow = $this->Followers->newEntity();
			$follow->user_id = $userid;
			$follow->follow_user_id = $logusrid;
			$result = $this->Followers->save($follow);
			$followId = $result->id;
			$flwrscnt = $this->Followers->find()->where(['user_id' => $userid])->count();
			$this->Shops->updateAll(array('follow_count' => $flwrscnt), array('user_id' => $userid));
			$sitesettingstable = TableRegistry::get('Sitesettings');
			$userstable = TableRegistry::get('Users');
			$loguser = $userstable->find('all')->where(['Users.id' => $logusrid])->first();
			$usrdetails = $userstable->find('all')->where(['Users.id' => $userid])->first();
			$notificationSettings = json_decode($usrdetails['push_notifications'], true);
			if ($notificationSettings['somone_flw_push'] == 1) {
				$logusernameurl = $loguser['username_url'];
				$logfirstname = $loguser['first_name'];
				$userDesc = $loguser['about'];
				$userImg = $loguser['profile_image'];
				if (empty($userImg)) {
					$userImg = 'usrimg.jpg';
				}
				$image['user']['image'] = $userImg;
				$image['user']['link'] = SITE_URL . "people/" . $logusernameurl;
				$loguserimage = json_encode($image);
				$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logfirstname . "</a>";
				$notifymsg = $loguserlink . " -___-is now following you";
				$logdetails = $this->addlog('follow', $logusrid, $userid, $followId, $notifymsg, $userDesc, $loguserimage);

				/* send pushnotifications */
				$userdevicestable = TableRegistry::get('Userdevices');
				$userddett = $userdevicestable->find('all')->where(['user_id' => $userid])->all();
				foreach ($userddett as $userdet) {
					$deviceTToken = $userdet['deviceToken'];
					$badge = $userdet['badge'];
					$badge += 1;
					$user_detail = TableRegistry::get('Users')->find()->where(['id' => $userid])->first();
					$querys = $userdevicestable->query();
					$querys->update()
					->set(['badge' => $badge])
					->where(['deviceToken' => $deviceTToken])
					->execute();
					if (isset($deviceTToken)) {
						$pushMessage['user_id'] = $logusrid;
						$pushMessage['type'] = 'follow';
						$pushMessage['user_name'] = $loguser['username'];
						$pushMessage['user_image'] = $userImg;
						I18n::locale($user_detail['languagecode']);
						$pushMessage['message'] = __d('user', 'is now following you');
						$messages = json_encode($pushMessage);
						$this->pushnot($deviceTToken, $messages, $badge);
					}
				}

			}

			/* send mail */
			$user_email = $userstable->find('all')->where(['Users.id' => $userid])->first();
			$emailaddress = $user_email['email'];
			$name = $user_email['first_name'];
			$follow_status = $user_email['someone_follow'];
			$username_url = $loguser['username_url'];
			$setngs = $sitesettingstable->find()->where(['id' => 1])->first();
			if ($follow_status == 1) {
				$email = $emailaddress;
				$aSubject = $setngs['site_name'] . " - " . $loguser['first_name'] . " " . __d('user', 'Following you on') . " " . $setngs['site_name'];
				$template = 'followmail';
				$setdata = array('name' => $name, 'username_url' => $username_url, 'email' => $email_address, 'username' => $loguser['first_name'], 'access_url' => SITE_URL . 'login', 'setngs' => $setngs);
				$this->sendmail($emailaddress, $aSubject, '', $template, $setdata);

			}

			echo '{"status":"true","message":"Successfully Followed"}';
			die;
		}
	}

	/* Unfollow User */
	function unfollowUser()
	{
		$logusrid = $_POST['user_id'];
		$userid = $_POST['follow_id'];
		$this->loadModel('Followers');
		$this->loadModel('Shops');
		$this->loadModel('Users');
		$this->loadModel('Userdevices');
		$flwalrdy = $this->Followers->find()->where(['user_id' => $userid])->andWhere(['follow_user_id' => $logusrid])->count();
		if ($flwalrdy > 0) {
			$this->Followers->deleteAll(array('user_id' => $userid, 'follow_user_id' => $logusrid));
			$flwrscnt = $this->Followers->find()->where(['user_id' => $userid])->count();
			$this->Shops->updateAll(array('follow_count' => $flwrscnt), array('user_id' => $userid));
			echo '{"status":"true","message":"Successfully Unfollowed"}';
			die;

		} else {
			echo '{"status":"false","message": "Something went to be wrong,Please try again later."}';
			die;
		}
	}

	function followStore()
	{

		$this->loadModel('Shops');
		$this->loadModel('Storefollowers');

		$storeid = $_POST['store_id'];
		$userid = $_POST['user_id'];

		if ($storeid && $userid) {
			$usrdetails = $this->Shops->find()->where(['id' => $storeid])->first();//first',array('conditions'=>array('Shop.id'=>$storeid)));
			$shopuserid = $usrdetails['user_id'];
			$shopflwrs = $usrdetails['follow_count'];

			$flwalrdy = $this->Storefollowers->find()->where(['store_id' => $storeid])->andWhere(['follow_user_id' => $userid])->count();//,array('conditions'=>array('store_id'=>$storeid,'follow_user_id'=>$userid)));
			if ($flwalrdy > 0) {
				echo '{"status":"false","message":"Following Already"}';
				die;
			} else {
				if ($userid != $shopuserid) {
					$storefollow = $this->Storefollowers->newEntity();
					$storefollow->store_id = $storeid;
					$storefollow->follow_user_id = $userid;
					$result = $this->Storefollowers->save($storefollow);

					$followId = $result->id;
					$totalflwrs = $shopflwrs + 1;

					$this->Shops->updateAll(array('follow_count' => $totalflwrs), array('user_id' => $shopuserid));
					echo '{"status":"true","message":"Successfully Followed"}';
					die;
				} else {
					echo '{"status":"false","message":"You can not follow your own store"}';
					die;
				}
			}

		}
	}

	function unfollowStore()
	{

		$this->loadModel('Shops');
		$this->loadModel('Storefollowers');

		$storeid = $_POST['store_id'];
		$userid = $_POST['user_id'];

		if ($storeid && $userid) {
			$usrdetails = $this->Shops->find()->where(['id' => $storeid])->first();//first',array('conditions'=>array('Shop.id'=>$storeid)));
			$shopuserid = $usrdetails['user_id'];
			$shopflwrs = $usrdetails['follow_count'];

			$flwalrdy = $this->Storefollowers->find()->where(['store_id' => $storeid])->andWhere(['follow_user_id' => $userid])->count();//,array('conditions'=>array('store_id'=>$storeid,'follow_user_id'=>$userid)));
			if ($flwalrdy > 0) {
				$this->Storefollowers->deleteAll(array('store_id' => $storeid, 'follow_user_id' => $userid));
				if ($shopflwrs > 0) {
					$totalflwrs = $shopflwrs - 1;
				} else {
					$totalflwrs = 0;
				}
				$this->Shops->updateAll(array('follow_count' => $totalflwrs), array('user_id' => $shopuserid));
				echo '{"status":"true","message":"Successfully Unollowed"}';
				die;
			} else {
				echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
				die;
			}
		}
	}

	function storeProducts()
	{

		$this->loadModel('Items');
		$store_id = $_POST['store_id'];
		$user_id = $_POST['user_id'];

		$item_data = $this->Items->find()->where(['shop_id' => $store_id])->count();
		$offset = 0;
		$limit = 10;

		if (!empty($_POST['offset'])) {
			$offset = $_POST['offset'];
		}
		if (!empty($_POST['limit'])) {
			$limit = $_POST['limit'];
		}
		if (!empty($store_id)) {
			if ($item_data == 0) {
				echo '{"status":"false","message":"No data found"}';
				die;
			}

			if (!empty($_POST['offset'])) {
				$items_data = $this->Items->find('all', array(
					'conditions' => array(
						'shop_id' => $store_id,
						'status' => 'publish',
					),
					'limit' => $limit,
					'offset' => $_POST['offset'],

				))->contain('Forexrates');

			} else {

				$items_data = $this->Items->find('all', array(
					'conditions' => array(
						'shop_id' => $store_id,
						'status' => 'publish',
					),
					'limit' => $limit,
				))->contain('Forexrates');
			}
		}
		if (empty($items_data)) {

			echo '{"status":"false","message":"No data found"}';
			die;
		} else {
			$resultArray = $this->convertJsonHome($items_data, $favitems_ids, $user_id);
			echo '{"status":"true","items":' . json_encode($resultArray) . '}';
			die;
		}

	}

	function additemReviews()
	{
		$this->loadModel('Itemreviews');
		$userstable = TableRegistry::get('Users');
		$itemstable = TableRegistry::get('Items');
		$itemreviewTable = TableRegistry::get('Itemreviews');
		
		
		//$reviewtitle = $_POST['review_title'];
		$reviews = $_POST['review'];
		$rating = $_POST['rating'];
		$user_id = $_POST['userid'];
		$item_id = $_POST['itemid'];
		$order_id = $_POST['orderid'];

		if(isset($_POST['review_id']) && $_POST['review_id'] != '')
        {
        	$this->Itemreviews->updateAll(array('reviews' => $reviews,'ratings'=>$rating), array('id' => $_POST['review_id']));

        	//Item ratings.
        	$getAvg = $this->getAverage($item_id);
        	//Seller ratings.
        	$getsellerAvg = $this->getsellerAverage($itemData->user_id);

			$querys = $itemstable->query();
					$querys->update()
					->set(['avg_rating' => $getAvg['rating']])
					->where(['id' => $item_id])
					->execute();

			$querys = $userstable->query();
					$querys->update()
					->set(['seller_ratings' => $getsellerAvg['rating']])
					->where(['id' => $itemData->user_id])
					->execute();
        	echo '{"status":"true","message":"Successfully updated."}';
			die;
        }
		
		
        $itemreview = $itemreviewTable->newEntity();

        $this->loadModel('Items');
        $itemData = $this->Items->find()->where(['id' => $item_id])->first();

        //print_r($itemData); die;

        //echo floatval($rating); die;

		$itemreview->userid = $user_id;
		$itemreview->itemid = $item_id;
		$itemreview->orderid = $order_id;
		$itemreview->seller_id = $itemData->user_id;
		//$itemreview->review_title = $reviewtitle;
		$itemreview->reviews = $reviews;
		$itemreview->ratings = floatval($rating);
		$result = $itemreviewTable->save($itemreview);


		$getAvg = $this->getAverage($item_id);

		//Seller average rating.
		$getsellerAvg = $this->getsellerAverage($itemData->user_id);

		$querys = $itemstable->query();
				$querys->update()
				->set(['avg_rating' => $getAvg['rating']])
				->where(['id' => $item_id])
				->execute();

		$querys = $userstable->query();
				$querys->update()
				->set(['seller_ratings' => $getsellerAvg['rating']])
				->where(['id' => $itemData->user_id])
				->execute();
		
		if (empty($result)) {

			echo '{"status":"false","message":"Please try again."}';
			die;
		} else {
			echo '{"status":"true","result":"Reviews added successfully."}';
			die;
		}
	}



	function getreviews()
	{
		$this->loadModel('Itemreviews');
		$this->loadModel('Users');

		//$user_id = $_POST['user_id'];
		$item_id = $_POST['item_id'];
		$offset = $_POST['offset'];
		$limit = $_POST['limit'];
		$itemreviewTable = TableRegistry::get('Itemreviews');
		$reviews = $this->Itemreviews->find('all', array(
				'conditions' => array(
					'itemid' => $item_id
				),
				'limit' => $_POST['limit'],
				'offset' => $_POST['offset'],
				'order' => 'id DESC',
			))->all();
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}



		$getAvgrat = $this->getAverage($item_id);
		$result = array();
		foreach($reviews as $key=>$eachreview)
		{
			$user_data = $this->Users->find()->where(['id' => $eachreview['userid']])->first();
			$result[$key]['user_id'] = $eachreview['userid'];
			$result[$key]['user_name'] = $user_data['username'];
			$result[$key]['user_image'] = ($user_data['profile_image'] != '') ? $img_path."media/avatars/thumb70/".$user_data['profile_image'] : $img_path."media/avatars/thumb70/usrimg.jpg";
			$result[$key]['id'] = $eachreview['orderid'];
			$result[$key]['review_title'] = $eachreview['review_title'];
			$result[$key]['rating'] = $eachreview['ratings'];
			$result[$key]['review'] = $eachreview['reviews'];
		}
		$results = json_encode($result);
		echo '{"status":"true",
				"review_count":'.$getAvgrat['reviews'].',
				"rating":'.$getAvgrat['rating'].',
				"result":'.$results.'}';
		die;

	}


	function getitemreviews($item_id = '')
	{
		$this->loadModel('Itemreviews');
		$this->loadModel('Users');

		//$user_id = $_POST['user_id'];
		$item_id = $_POST['item_id'];
		$offset = $_POST['offset'];
		$limit = $_POST['limit'];
		$itemreviewTable = TableRegistry::get('Itemreviews');
		$reviews = $this->Itemreviews->find('all', array(
				'conditions' => array(
					'itemid' => $item_id
				),
				'limit' => 2,
				'offset' => 0,
				'order' => 'id DESC',
			))->all();

		$getAvgrat = $this->getAverage($item_id);
		$result = array();
		foreach($reviews as $key=>$eachreview)
		{
			$user_data = $this->Users->find()->where(['id' => $eachreview['userid']])->first();
			$result[$key]['user_id'] = $eachreview['userid'];
			$result[$key]['user_name'] = $user_data['username'];
			$result[$key]['id'] = $eachreview['orderid'];
			$result[$key]['review_title'] = $eachreview['review_title'];
			$result[$key]['rating'] = $eachreview['ratings'];
			$result[$key]['review'] = $eachreview['reviews'];
		}
		$results = array('result'=>$result,
						'review_count'=>$getAvgrat['reviews'],
						'rating'=>$getAvgrat['rating']);
		return $results;
		//die;

	}

	function getAverage($value='')
	{
		$this->loadModel('Itemreviews');
		$itemreviewTable = TableRegistry::get('Itemreviews');
		$reviews = $this->Itemreviews->find('all', array(
				'conditions' => array(
					'itemid' => $value
				),
				'order' => 'id DESC'
			))->all();
		
		$max = 0;
        $n = count($reviews); // get the count of comments 
        foreach ($reviews as $rate => $count) { // iterate through array

           	$max = $max+$count->ratings;
        }
        $Rating = ($n != 0) ? $max / $n : 0;
        return array('reviews'=>$n, 'rating'=>round($Rating,1));
	}


	function getsellerAverage($value='')
	{
		$this->loadModel('Itemreviews');
		$itemreviewTable = TableRegistry::get('Itemreviews');
		$reviews = $this->Itemreviews->find('all', array(
				'conditions' => array(
					'seller_id' => $value
				),
				'order' => 'id DESC'
			))->all();
		
		$max = 0;
        $n = count($reviews); // get the count of comments 
        foreach ($reviews as $rate => $count) { // iterate through array

           	$max = $max+$count->ratings;
        }
        $Rating = ($n != 0) ? $max / $n : 0;
        return array('reviews'=>$n, 'rating'=>round($Rating,1));
	}


	function getStoreAverage($value='')
	{
		$this->loadModel('Itemreviews');
		$itemreviewTable = TableRegistry::get('Itemreviews');
		$reviews = $this->Itemreviews->find('all', array(
				'conditions' => array(
					'seller_id' => $value
				),
				'order' => 'id DESC'
			))->all();
		
		$max = 0;
        $n = count($reviews); // get the count of comments 
        foreach ($reviews as $rate => $count) { // iterate through array

           	$max = $max+$count->ratings;
        }
        $Rating = ($n != 0) ? $max / $n : 0;
        return round($Rating,1);
	}
	function storeReviews()
	{

		$this->loadModel('Shops');
		$this->loadModel('Reviews');
		$this->loadModel('Photos');
		$this->loadModel('Users');
		$this->loadModel('Sitesettings');
		$this->loadModel('Order_items');
		$offset = 0;
		$limit = 10;
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}

		$store_id = $_POST['store_id'];
		if (!empty($_POST['offset'])) {
			$offset = $_POST['offset'];
			$limit = $_POST['limit'];
		}
		

		$itemreviewTable = TableRegistry::get('Itemreviews');
		$this->loadModel('Itemreviews');


		if (!empty($store_id)) {
			$store_data = $this->Shops->find()->where(['id' => $store_id])->first();
			$sellerid = $store_data['user_id'];
			
			if (isset($_POST['offset'])) {
				$reviews = $this->Itemreviews->find('all', array(
						'conditions' => array(
							'seller_id' => $sellerid
						),
						'limit' => $_POST['limit'],
						'offset' => $_POST['offset'],
						'order' => 'id DESC',
					))->all();
			} else {
				$reviews = $this->Itemreviews->find('all', array(
						'conditions' => array(
							'seller_id' => $sellerid
						),
						'limit' => 10,
						'offset' => 0,
						'order' => 'id DESC',
					))->all();
				//echo 'emty';
			}

			//echo '<pre>'; print_r($reviews); die;

			$reviewContent = array();
			foreach ($reviews as $reviewkey => $review) {

				//echo '<pre>'; print_r($review); die;
				$reviewContent[$reviewkey]['review_id'] = $review->id;
				$reviewContent[$reviewkey]['user_id'] = $review->userid;
				$user_data = $this->Users->find()->where(['id' => $review->userid])->first();
				$reviewContent[$reviewkey]['full_name'] = $user_data->first_name . '' . $user_data->last_name;
				$reviewContent[$reviewkey]['user_name'] = $user_data->username;
				$reviewContent[$reviewkey]['user_image'] = $img_path . 'media/avatars/thumb70/' . $user_data->profile_image;
				
				$order_data = $this->Order_items->find()->where(['orderid' => $review->orderid])->first();
				$reviewContent[$reviewkey]['item_id'] = $order_data->itemid;


				$item_data = $this->Photos->find()->where(['item_id' => $order_data->itemid])->first();

				/*
				if (!empty($_POST['logged_user_id'])) {
					$loggedUserFav = $this->Itemfavs->find()->where(['user_id' => $_POST['logged_user_id']])->all();
					if (count($loggedUserFav) > 0) {
						foreach ($loggedUserFav as $logfavitems) {
							$loggedUserFav_ids[] = $logfavitems['item_id'];
						}
					} else {
						$loggedUserFav_ids = array();
					}
				}
				*/
				
				$reviewContent[$reviewkey]['item_image'] = $img_path . 'media/items/thumb150/' . $item_data->image_name;

				//$itemArr = array($order_data->itemid);
				//$resultArray = $this->convertJsonHome($itemArr, $loggedUserFav_ids, $_POST['logged_user_id']);

				$item_arr = $this->getsingleitem($review->itemid, $_POST['user_id']);
				$reviewContent[$reviewkey]['item_title'] = $order_data->itemname;
				$reviewContent[$reviewkey]['title'] = $review->review_title;
				$reviewContent[$reviewkey]['message'] = $review->reviews;
				$reviewContent[$reviewkey]['rating'] = $review->ratings;
				$reviewContent[$reviewkey]['product'] = $item_arr;	
				$reviewContent[$reviewkey]['visible'] = (empty($item_arr) || $item_arr['id'] == null) ? 'false' : 'true';	
			}

			//echo '<pre>'; print_r($reviewContent);
			//die;
			//echo '<pre>'; print_r($reviewContent); die;
		}
		//die;


		if (count($reviewContent) != 0) {
			$reviewContent = json_encode($reviewContent);

			echo '{"status":"true","result":' . $reviewContent . '}';
			die;
		} else {
			echo '{"status": "false", "message": "No data found"}';
			die;

		}
	}
	
	function storeNews()
	{
		$this->loadModel('Logs');
		$this->loadModel('Shops');
		$store_id = $_POST['store_id'];
		$user_id = $_POST['user_id'];

		$shop_data = $this->Shops->find()->where(['id' => $store_id])->first();
		$sellerid = $shop_data['user_id'];

		if (!empty($_POST['offset'])) {
			$offset = $_POST['offset'];
		}
		if (!empty($_POST['limit'])) {
			$limit = $_POST['limit'];
		}

		if (!empty($store_id)) {

			if (!empty($_POST['offset'])) {
				$log_data = $this->Logs->find('all', array(
					'conditions' => array(
						'notifyto' => 0,
						'userid'=>$sellerid,
						'type' => 'sellermessage',
					),
					'limit' => $limit,
					'offset' => $_POST['offset'],
					'order' => 'id DESC',

				));

			} else {

				$log_data = $this->Logs->find('all', array(
					'conditions' => array(
						'notifyto' =>0,
						'userid'=>$sellerid,
						'type' => 'sellermessage',
					),
					'limit' => $limit,
					'order' => 'id DESC',
				));
			}
			$newsContent = array();
			foreach ($log_data as $logkey => $logs) {
				$newsContent[$logkey]['message'] = $logs['message'];
				$newsContent[$logkey]['date'] = $logs['cdate'];
			}

		}

		if (count($newsContent) != 0) {
			$newsContent = json_encode($newsContent);

			echo '{"status":"true","result":' . $newsContent . '}';
			die;
		} else {
			echo '{"status": "false", "message": "No data found"}';
			die;

		}
	}

	function storeProfile()
	{

		$storeid = $_POST['store_id'];
		$userid = $_POST['user_id'];
		$this->loadModel('Users');
		$this->loadModel('Shops');
		$this->loadModel('Storefollowers');
		$this->loadModel('Items');
		//$this->loadModel('Reviews');
		$this->loadModel('Itemreviews');
		$this->loadModel('Orders');
		$this->loadModel('Sitesettings');
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}

		//echo $storeid; die;
		if (!empty($storeid)) {

			$shopsdet = $this->Shops->find()->where(['seller_status' => '1'])->andWhere(['id' => $storeid])->first();

			if (count($shopsdet) == 0) {
				echo '{"status": "false", "message": "No data found"}';
				die;
			}

			$shop_id = $shopsdet['id'];
			$shop_user_id = $shopsdet['user_id'];

			//$getSellerData = $this->Users->find()->where(['id'=>$shop_user_id])->first();
			$sellerAvgRate = $this->getsellerAverage($shop_user_id);

			$storeRating = $sellerAvgRate['rating'];

			$reviews = $this->Itemreviews->find()->where(['seller_id' => $shop_user_id])->all();//all',])->all();//('conditions'=>array('Review.sellerid'=>$shop_user_id)));
			$review_count = count($reviews);

		//$ordersModel = $this->Orders->find('all',array('conditions'=>array(    chant_id'=>$shop_user_id)));
		//$salescount = count($ordersModel);

			$resultarray['store_id'] = $shopsdet['id'];
			$resultarray['store_rating'] = $storeRating;
			$resultarray['store_name'] = $shopsdet['shop_name'];
			$resultarray['merchant_name'] = $shopsdet['merchant_name'];
			$resultarray['store_address'] = $shopsdet['shop_address'];
			$resultarray['lat'] = $shopsdet['shop_latitude'];
			$resultarray['lon'] = $shopsdet['shop_longitude'];

		//$resultarray['salesCount'] = $salescount;

			$shop_image = $shopsdet['shop_image'];
			$shop_banner = $shopsdet['shop_banner'];

			if ($shop_image == "")
				$shop_image = "usrimg.jpg";

			if ($shop_banner == "")
				$shop_banner = "usrimg.jpg";
			$resultarray['store_image'] = $img_path . 'media/avatars/thumb350/' . $shop_image;
		//$resultarray['imageName']['thumb150'] = $img_path.'media/avatars/thumb150/'.$shop_image;
			$resultarray['banner_image'] = $img_path . 'media/avatars/original/' . $shop_banner;

			$followdet = $this->Storefollowers->find()->where(['store_id' => $shop_id])->all();//all',array('conditions'=>array('Storefollower.store_id'=>$shop_id)));
		//print_r($followdet);
			$followcntshop = count($followdet);

			$resultarray['store_followers'] = $followcntshop;

			$addeditem_details = $this->Items->find()->where(['shop_id' => $storeid])->all();//all',array('conditions'=>array('Item.user_id'=>$shop_user_id),'order'=>array('Item.id DESC')));

			$addedcount = count($addeditem_details);

			$resultarray['product_count'] = $addedcount;
			$resultarray['reviewCount'] = "$review_count";
			$user_data = $this->Users->find()->where(['id' => $shop_user_id])->first();
			//$resultarray['average_rating'] = $user_data['seller_ratings'];
			$resultarray['average_rating'] = $storeRating;

			$follow_data = $followdet = $this->Storefollowers->find()->where(['store_id' => $shop_id])->andWhere(['follow_user_id' => $userid])->all();//->all();//all',array('conditions'=>array('Storefollower.store_id'=>$shop_id)));
			if (count($follow_data) == 0)
				$resultarray['follow_status'] = "follow";
			else
				$resultarray['follow_status'] = "unfollow";

		/*if($userid)
		{
			$followcnt = $this->Storefollower->followcnt($userid);
			foreach($followcnt as $flcnt){
				$flwrcntid[] = $flcnt['Storefollower']['store_id'];
			}
			if(in_array($shop_id,$flwrcntid))
			{
				$following = "yes";
			}
			else
			{
				$following = "no";
			}
		}*/

		$resultarray = json_encode($resultarray);
		echo '{"status":"true","result":' . $resultarray . '}';
		die;
	} else {

		echo '{"status": "false", "message": "No data found"}';
		die;

	}
}

function productBeforeadd()
{

	$this->loadModel('Categories');
	$this->loadModel('Recipients');
	$this->loadModel('Countries');
	$this->loadModel('Sitesettings');

	$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
	$resultarray = array();

		$CategoryModel = $this->Categories->find()->where(['category_parent' => 0])->toArray();//all',array('conditions'=>array('category_parent'=>'0')));
		if (count($CategoryModel) > 0) {
			for ($i = 0; $i < count($CategoryModel); $i++) {
				$resultarray[$i] = array();
				$categoryId = $CategoryModel[$i]['id'];
				$resultarray[$i]['id'] = $categoryId;
				$resultarray[$i]['name'] = $CategoryModel[$i]['category_name'];
				$cat_image = $CategoryModel[$i]['category_image'];
					//$resultarray[$i]['icon'] = SITE_URL.'images/category/'.$cat_image;
				if ($cat_image == "") {
					$resultarray[$i]['icon'] = SITE_URL . 'media/avatars/thumb70/usrimg.jpg';
				} else {

						//$imageName = $photo['image_name'];

					$imageName = WWW_ROOT . 'images/category/' . $cat_image;

					if (file_exists($imageName)) {
						$resultarray[$i]['icon'] = SITE_URL . 'images/category/' . $cat_image;

					} else {
						$resultarray[$i]['icon'] = SITE_URL . 'media/avatars/thumb70/usrimg.jpg';
					}

				}

				$resultarray[$i]['sub_category'] = array();
				$subcategoryModel = $this->Categories->find()->where(['category_parent' => $categoryId])->andWhere(['category_sub_parent' => 0])->toArray();//$this->Category->find('all',array('conditions'=>array('category_parent'=>$categoryId,'category_sub_parent'=>'0')));
				if (count($subcategoryModel) > 0) {
					for ($j = 0; $j < count($subcategoryModel); $j++) {
						$subcatid = $subcategoryModel[$j]['id'];
						$subname = $subcategoryModel[$j]['category_name'];
						$resultarray[$i]['sub_category'][$j] = array();
						$resultarray[$i]['sub_category'][$j]['id'] = $subcatid;
						$resultarray[$i]['sub_category'][$j]['name'] = $subname;
						$resultarray[$i]['sub_category'][$j]['super_category'] = array();
						$subcatModel = $this->Categories->find()->where(['category_parent' => $categoryId])->andWhere(['category_sub_parent' => $subcatid])->toArray();
						if (count($subcatModel) > 0) {
							for ($k = 0; $k < count($subcatModel); $k++) {
								$resultarray[$i]['sub_category'][$j]['super_category'][$k] = array();
								$subsubid = $subcatModel[$k]['id'];
								$subsubname = $subcatModel[$k]['category_name'];
								$resultarray[$i]['sub_category'][$j]['super_category'][$k]['id'] = $subsubid;
								$resultarray[$i]['sub_category'][$j]['super_category'][$k]['name'] = $subsubname;
							}
						}
					}
				}
			}

		}

		$resultarray1 = array();
		$colorModel = $this->Recipients->find()->toArray();
		if (count($colorModel) > 0) {
			for ($i = 0; $i < count($colorModel); $i++) {
				$resultarray1[$i] = array();
				$resultarray1[$i]['id'] = $colorModel[$i]['id'];
				$resultarray1[$i]['name'] = $colorModel[$i]['recipient_name'];
			}
		}

		$resultarray2 = array();
		$colorModel = $this->Countries->find()->toArray();
		if (count($colorModel) > 0) {
			for ($i = 0; $i < count($colorModel); $i++) {
				$resultarray2[$i] = array();
				$resultarray2[$i]['id'] = $colorModel[$i]['id'];
				$resultarray2[$i]['code'] = $colorModel[$i]['code'];
				$resultarray2[$i]['name'] = $colorModel[$i]['country'];
			}
		}

		$this->loadModel('Colors');
		$resultarray3 = array();
		$color_datas = $this->Colors->find()->toArray();
		if (count($color_datas) > 0) {
			for ($i = 0; $i < count($color_datas); $i++) {
				$resultarray3[$i] = array();
				$resultarray3[$i]['name'] = $color_datas[$i]['color_name'];

			}
		}

		$resultarray4 = array();
				/*$resultarray['gender'][0]['id'] = '0';
				$resultarray['gender'][0]['Name'] = 'Male';
				$resultarray['gender'][1]['id'] = '1';
				$resultarray['gender'][1]['Name'] = 'Female';*/

				$genderSettings = json_decode($setngs[0]['gender_type'], true);
				foreach ($genderSettings as $key => $gender) {
					$resultarray4[$key] = array();
					$resultarray4[$key]['id'] = $key;
					$resultarray4[$key]['name'] = $gender;
				}
				//echo "<pre>";print_r($resultarray['gender']);die;

				$resultarray5 = array();
				$resultarray5[0]['id'] = '1d';
				$resultarray5[0]['time'] = '1 business day';
				$resultarray5[1]['id'] = '2d';
				$resultarray5[1]['time'] = '1-2 business days';
				$resultarray5[2]['id'] = '3d';
				$resultarray5[2]['time'] = '1-3 business days';
				$resultarray5[3]['id'] = '4d';
				$resultarray5[3]['time'] = '3-5 business days';
				$resultarray5[4]['id'] = '2ww';
				$resultarray5[4]['time'] = '1-2 weeks';
				$resultarray5[5]['id'] = '3w';
				$resultarray5[5]['time'] = '2-3 weeks';
				$resultarray5[6]['id'] = '4w';
				$resultarray5[6]['time'] = '3-4 weeks';
				$resultarray5[7]['id'] = '6w';
				$resultarray5[7]['time'] = '4-6 weeks';
				$resultarray5[8]['id'] = '8w';
				$resultarray5[8]['time'] = '6-8 weeks';

				$resultarray = json_encode($resultarray);
				$resultarray1 = json_encode($resultarray1);
				$resultarray2 = json_encode($resultarray2);
				$resultarray3 = json_encode($resultarray3);
				$resultarray4 = json_encode($resultarray4);
				$resultarray5 = json_encode($resultarray5);
				echo '{"status":"true","category":' . $resultarray . ', "relation_ship":' . $resultarray1 . ', "country":' . $resultarray2 . ', "color":' . $resultarray3 . ', "gender":' . $resultarray4 . ', "ship_delivery_time":' . $resultarray5 . '}';
				die;

			}

			public function getAddress()
			{

				$userId = $_POST["user_id"];

				$this->loadModel('Tempaddresses');
				$this->loadModel('Users');
		$userModel = $this->Users->find()->where(['id' => $userId])->first();//User($userId);
		$defaultShipping = $userModel['defaultshipping'];
		$shippingModel = $this->Tempaddresses->find()->where(['userid' => $userId])->all();//all',array('conditions'=>array('userid'=>$userId)));

		if (count($shippingModel) != 0) {
			$shippingAddress = array();
			foreach ($shippingModel as $skey => $shipping) {
				$shippingAddress[$skey]['shipping_id'] = $shipping['shippingid'];
				$shippingAddress[$skey]['nick_name'] = $shipping['nickname'];
				$shippingAddress[$skey]['full_name'] = $shipping['name'];

				$shippingAddress[$skey]['address1'] = $shipping['address1'];
				$shippingAddress[$skey]['address2'] = $shipping['address2'];
				$shippingAddress[$skey]['city'] = $shipping['city'];
				$shippingAddress[$skey]['state'] = $shipping['state'];
				$shippingAddress[$skey]['country'] = $shipping['country'];

				$shippingAddress[$skey]['zipcode'] = $shipping['zipcode'];
				$shippingAddress[$skey]['phone_no'] = $shipping['phone'];
				$shippingAddress[$skey]['country_id'] = $shipping['countrycode'];
				$shippingAddress[$skey]['default'] = 0;
				if ($defaultShipping == $shipping['shippingid']) {
					$shippingAddress[$skey]['default'] = 1;
				}
			}
			$resultArray = json_encode($shippingAddress);
			echo '{"status":"true","result":' . $resultArray . '}';
			die;

		} else {
			echo '{"status":"false","message":"No address found"}';
			die;

		}
	}

	function addAddress()
	{

		$shippingId = 0;
		if (!empty($_POST['shipping_id'])) {
			$shippingId = $_POST['shipping_id'];
		}
		$userId = $_POST['user_id'];
		$fullName = $_POST['full_name'];
		$nickName = $_POST['nick_name'];
		$countryId = $_POST['country_id'];
		$countryName = $_POST['country_name'];
		$state = $_POST['state'];
		$address1 = $_POST['address1'];
		$address2 = $_POST['address2'];
		$city = $_POST['city'];
		$zipCode = $_POST['zipcode'];
		$phoneNo = $_POST['phone_no'];

		$this->loadModel('Tempaddresses');
		$this->loadModel('Users');

		/* if ($shippingId == 0) {
			$shippingModel = $this->Tempaddresses->find()->where(['nickname' => $nickName])->all();
		} else {
			$shippingModel = $this->Tempaddresses->find()->where(['nickname' => $nickName])->andWhere(['shippingid <>' => $shippingId])->all();
		}
			//echo"<pre>";echo $shippingId; print_r($shippingModel);die;
		if (count($shippingModel) != 0) {
			echo '{"status":"false","message":"Already a Shipping Address with this Nick Name Exist"}';
			die;
		} else { */

			if ($shippingId == 0) {

				$outputValue = "Added";
				$address = $this->Tempaddresses->newEntity();
				$address->userid = $userId;
				$address->nickname = $nickName;
				$address->name = $fullName;
				$address->address1 = $address1;
				$address->address2 = $address2;
				$address->city = $city;
				$address->state = $state;
				$address->country = $countryName;
				$address->zipcode = $zipCode;
				$address->phone = $phoneNo;
				$address->countrycode = $countryId;

				$result = $this->Tempaddresses->save($address);
				$id = $result->shippingid;
				$temp_data = $this->Tempaddresses->find()->where(['userid' => $userId])->all();
				if (count($temp_data) == 1) {
					$userModel = $this->Users->find()->where(['id' => $userId])->first();
					$userModel->defaultshipping = $id;
					$this->Users->save($userModel);
				}
				$shipping = $this->Tempaddresses->find()->where(['shippingid' => $id])->first();

			} else {
				$outputValue = "Updated";
				$shipping = $this->Tempaddresses->find()->where(['shippingid' => $shippingId])->first();
				$this->Tempaddresses->updateAll(array('userid' => $userId, 'name' => $fullName, 'nickname' => $nickName, 'country' => $countryName, 'state' => $state, 'address1' => $address1, 'address2' => $address2, 'city' => $city, 'zipcode' => $zipCode, 'phone' => $phoneNo, 'countrycode' => $countryId), array('shippingid' => $shippingId));
			}

		$userModel = $this->Users->find()->where(['id' => $userId])->first();//User($userId);
		$defaultShipping = $userModel['defaultshipping'];

		$shippingAddress['shipping_id'] = $shipping['shippingid'];
		$shippingAddress['nick_name'] = $shipping['nickname'];
		$shippingAddress['full_name'] = $shipping['name'];
		$shippingAddress['address1'] = $shipping['address1'];
		$shippingAddress['address2'] = $shipping['address2'];
		$shippingAddress['city'] = $shipping['city'];
		$shippingAddress['state'] = $shipping['state'];
		$shippingAddress['country'] = $shipping['country'];
		$shippingAddress['zip_code'] = $shipping['zipcode'];
		$shippingAddress['phone_no'] = $shipping['phone'];
		$shippingAddress['country_id'] = $shipping['countrycode'];
		$shippingAddress['default'] = 0;
		if ($defaultShipping == $shipping['shippingid']) {
			$shippingAddress['default'] = 1;
		}
		$output = json_encode($shippingAddress);
		echo '{"status":"true","message":"Successfully Added","result":' . $output . '}';
		die;
	}

	function defaultAddress()
	{

		$this->loadModel('Users');
		$this->loadModel('Tempaddresses');
		$userId = $_POST['user_id'];

		$shippingId = $_POST['shipping_id'];
		$temp = $this->Tempaddresses->find()->where(['shippingid' => $shippingId])->andWhere(['userid' => $userId])->all();
		if (count($temp) != 0) {
			$this->Users->updateAll(array('defaultshipping' => $shippingId), array('id' => $userId));
			echo '{ "status": "true","message": "Your default Address changed "}';
			die;

		} else
		echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
		die;

	}

	public function removeAddress()
	{

		$userId = $_POST['user_id'];

		$shippingId = $_POST['shipping_id'];

		$this->loadModel('Tempaddresses');

		$status = $this->Tempaddresses->deleteAll(array('shippingid' => $shippingId, 'userid' => $userId));

		if ($status) {
			echo '{"status":"true","message":"Address Deleted Successfully"}';
			die;
		} else {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		}

	}

	function addToCart()
	{

		$this->loadModel('Carts');
		$userId = $_POST['user_id'];

		$itemId = $_POST['item_id'];
		$quantity = 1;
		if (!empty($_POST['qty'])) {
			$quantity = $_POST['qty'];
		}
		$size = $_POST['size'];
		$cart = $this->Carts->newEntity();
		$CartModel = $this->Carts->find()->where(['user_id' => $userId, ['item_id' => $itemId]])->andWhere(['payment_status' => 'progress', ['size_options' => $size]])->all();
		if (count($CartModel) == 0) {
			$cart->user_id = $userId;
			$cart->item_id = $itemId;
			$cart->payment_status = 'progress';
			$cart->created_at = date('Y-m-d H:s:m', time());
			$cart->modified_at = date('Y-m-d H:s:m', time());
			$cart->quantity = $quantity;
			$cart->size_options = $size;

			if ($this->Carts->save($cart)) {
				echo '{"status":"true","message":"Item added to cart"}';
				die;
			} else {
				echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			}
		} else {
			echo '{"status":"false","message":" Item already in cart"}';
			die;
		}

	}

	function changeCartQuantity()
	{

		$userId = $_POST['user_id'];
		$cartId = $_POST['cart_id'];
		$size = $_POST['size'];
		$quantity = $_POST['quantity'];
		$itemId = $_POST['item_id'];

		$this->loadModel('Carts');
		$this->loadModel('Items');
		
		if ($cartId == 0) {
			$itemModel = $this->Items->find()->where(['id' => $itemId])->first();
			if (count($itemModel) == 0) {
				echo '{"status":"false","message":"Item Cannot be Found "}';
				die;
			}

			if (!empty($size) && $size != ""){
				$sizeoptions = $itemModel['size_options'];
				$sizes = json_decode($sizeoptions, true);
				$availableQty = $sizes['unit'][$size];
			}
			else{
				$availableQty = $itemModel['quantity'];
			}

			if(is_nan($availableQty)) { $availableQty=0; }

			/* check availability in buynow */
			if ($availableQty < $quantity) {
				echo '{"status":"false","message":"Requested Quantity Not Available"}';
				die;

			}
			else{
				echo '{"status":"true","message":"Quantity Changed"}';
				die;
			}
			
		} else {
			$cartModel = $this->Carts->find()->where(['id' => $cartId, ['user_id' => $userId]])->andWhere(['payment_status' => 'progress'])->first();
			$size=$cartModel['size_options'];
			if (count($cartModel) != 0) {
				$itemModel = $this->Items->find()->where(['id' => $cartModel['item_id']])->first();
				if (!empty($size) && $size != ""){
					$sizeoptions = $itemModel['size_options'];
					$sizes = json_decode($sizeoptions, true);
					$availableQty = $sizes['unit'][$size];
				}
				else{
					$availableQty = $itemModel['quantity'];
				}

				if(is_nan($availableQty)) { $availableQty=0; }
				/* check availability in cart */
				if ($availableQty < $quantity) {
					echo '{"status":"false","message":"Requested Quantity Not Available"}';
					die;

				}
				else{
					$this->Carts->updateAll(array('quantity' => $quantity), array('id' => $cartId));
					echo '{"status":"true","message":"Quantity Changed"}';
					die;
				}
			} else {
				echo '{"status":"false","message":"Item Cannot be Found in Cart"}';
				die;
			}
		}
	}

	function removeCartItem()
	{

		$userId = $_POST['user_id'];

		$cartId = $_POST['cart_id'];

		$this->loadModel('Carts');

		$status = $this->Carts->deleteAll(array('user_id' => $userId, 'id' => $cartId));

		if ($status) {
			echo '{"status":"true","message":"Item removed from cart"}';
			die;
		} else {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		}

	}

	function followers()
	{

		$userId = $_POST['user_id'];
		$loggeduserId = $_POST['logged_user_id'];
		$this->loadModel('Users');
		$this->loadModel('Followers');
		$this->loadModel('Sitesettings');
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}

		$offset = 0;
		$limit = 10;
		if (isset($_GET['offset'])) {
			$offset = $_GET['offset'];
		}
		if (isset($_GET['limit'])) {
			$limit = $_GET['limit'];
		}

		if (!empty($_POST['offset'])) {
			$followModel = $this->Followers->find('all', array(
				'conditions' => array(
					'user_id' => $userId,
				),
				'limit' => $limit,
				'offset' => $_POST['offset'],

			));

		} else {

			$followModel = $this->Followers->find('all', array(
				'conditions' => array(
					'user_id' => $userId,
				),
				'limit' => $limit,
			));
		}

		$followModel1 = $this->Followers->find()->where(['follow_user_id' => $loggeduserId])->all();//

		if (count($followModel) > 0) {
			foreach ($followModel as $follower) {
				$followers[] = $follower['follow_user_id'];
			}

			foreach ($followModel1 as $follower1) {
				$followers_list[] = $follower1['user_id'];
			}
			if (count($followers) == 0) {
				echo '{"status":"false","message":"No Data Found"}';
				die;
			}
				//echo "<pre>";print_r($followers_list);die;
			$userModel = $this->Users->find()->where(['activation <>' => 0])->andWhere(['id IN' => $followers])->all();
			
			$resultArray = array();
			foreach ($userModel as $key => $value) {
				$resultArray[$key]['user_id'] = $value['id'];
				$resultArray[$key]['full_name'] = $value['first_name'] . ' ' . $value['last_name'];
				$resultArray[$key]['user_name'] = $value['username'];
				$imageName = $value['profile_image'];
				if ($imageName == '') {
					$imageName = "usrimg.jpg";
				}
				$resultArray[$key]['user_image'] = $img_path . 'media/avatars/thumb350/' . $imageName;
				
				if (in_array($value['id'], $followers_list)) {
					$resultArray[$key]['status'] = 'unfollow';
				} else {
					$resultArray[$key]['status'] = 'follow';
				}

			}
				//echo "<pre>";print_r($resultArray);die;
			$resultArray = json_encode($resultArray);
			echo '{"status":"true","result":' . $resultArray . '}';
			die;
		} else {
			echo '{"status":"false","message":"No Data Found"}';
			die;
		}

	}

	function followings()
	{

		$userId = $_POST['user_id'];

		$loggeduserId = $_POST['logged_user_id'];
		$this->loadModel('Users');
		$this->loadModel('Followers');

		$this->loadModel('Sitesettings');
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}

		$offset = 0;
		$limit = 10;
		if (isset($_POST['offset'])) {
			$offset = $_POST['offset'];
		}
		if (isset($_POST['limit'])) {
			$limit = $_POST['limit'];
		}

		if (!empty($_POST['offset'])) {
			$followModel = $this->Followers->find('all', array(
				'conditions' => array(
					'follow_user_id' => $userId,
				),
				'limit' => $limit,
				'offset' => $_POST['offset'],

			));

		} else {

			$followModel = $this->Followers->find('all', array(
				'conditions' => array(
					'follow_user_id' => $userId,
				),
				'limit' => $limit,
			));
		}

		$followModel1 = $this->Followers->find()->where(['follow_user_id' => $loggeduserId])->all();//
		if (count($followModel) > 0) {
			foreach ($followModel as $follower) {
				$followers[] = $follower['user_id'];
			}

			foreach ($followModel1 as $follower1) {
				$followers_list[] = $follower1['user_id'];
			}
			if (count($followers) == 0) {
				echo '{"status":"false","message":"No Data Found"}';
				die;
			}
				//echo "<pre>";print_r($followers);die;
			$userModel = $this->Users->find()->where(['activation <>' => 0])->andWhere(['id IN' => $followers])->all();

			$resultArray = array();
			foreach ($userModel as $key => $value) {
				$resultArray[$key]['user_id'] = $value['id'];
				$resultArray[$key]['full_name'] = $value['first_name'] . ' ' . $value['last_name'];
				$resultArray[$key]['user_name'] = $value['username'];
				$imageName = $value['profile_image'];
				if ($imageName == '') {
					$imageName = "usrimg.jpg";
				}
				$resultArray[$key]['user_image'] = $img_path . 'media/avatars/thumb350/' . $imageName;

				if (in_array($value['id'], $followers_list)) {
					$resultArray[$key]['status'] = 'unfollow';
				} else {
					$resultArray[$key]['status'] = 'follow';
				}

			}
				//echo "<pre>";print_r($resultArray);die;
			$resultArray = json_encode($resultArray);
			echo '{"status":"true","result":' . $resultArray . '}';
			die;
		} else {
			echo '{"status":"false","message":"No Data Found"}';
			die;
		}

	}

	function followingStores()
	{

		$userId = $_POST['user_id'];

		$loggeduserId = $_POST['logged_user_id'];
		$this->loadModel('Shops');
		$this->loadModel('Storefollowers');

		$this->loadModel('Sitesettings');
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}

		$offset = 0;
		$limit = 10;
		if (isset($_POST['offset'])) {
			$offset = $_POST['offset'];
		}
		if (isset($_POST['limit'])) {
			$limit = $_POST['limit'];
		}

		if (!empty($_POST['offset'])) {
			$followModel = $this->Storefollowers->find('all', array(
				'conditions' => array(
					'follow_user_id' => $userId,
				),
				'limit' => $limit,
				'offset' => $_POST['offset'],

			));

		} else {

			$followModel = $this->Storefollowers->find('all', array(
				'conditions' => array(
					'follow_user_id' => $userId,
				),
				'limit' => $limit,
			));
		}

		$followModel1 = $this->Storefollowers->find()->where(['follow_user_id' => $loggeduserId])->all();//
		if (count($followModel) > 0) {
			foreach ($followModel as $follower) {
				$followers[] = $follower['store_id'];
			}

			foreach ($followModel1 as $follower1) {
				$followers_list[] = $follower1['store_id'];
			}
				//echo "<pre>";print_r($followers);die;
			if (count($followers) == 0) {
				echo '{"status":"false","message":"No Data Found"}';
				die;
			}
			$userModel = $this->Shops->find()->where(['seller_status <>' => 0])->andWhere(['id IN' => $followers])->all();

			$resultArray = array();
			foreach ($userModel as $key => $value) {
				$resultArray[$key]['store_id'] = $value['id'];
				$resultArray[$key]['store_name'] = $value['shop_name'];
				$resultArray[$key]['wifi'] = $value['wifi'];
				$resultArray[$key]['merchant_name'] = $value['merchant_name'];
				if (in_array($value['id'], $followers_list)) {
					$resultArray[$key]['status'] = 'unfollow';
				} else {
					$resultArray[$key]['status'] = 'follow';
				}
				$imageName = $value['shop_image'];
				if ($imageName == '') {
					$imageName = "usrimg.jpg";
				}
				$resultArray[$key]['image'] = $img_path . 'media/avatars/thumb350/' . $imageName;

			}
				//echo "<pre>";print_r($resultArray);die;
			$resultArray = json_encode($resultArray);
			echo '{"status":"true","result":' . $resultArray . '}';
			die;
		} else {
			echo '{"status":"false","message":"No Data Found"}';
			die;
		}

	}

	function storeFollowers()
	{
		$storeId = $_POST['store_id'];
		$loggeduserId = $_POST['logged_user_id'];
		$this->loadModel('Shops');
		$this->loadModel('Storefollowers');
		$this->loadModel('Followers');
		$this->loadModel('Users');
		$this->loadModel('Sitesettings');
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}
		$offset = 0;
		$limit = 10;
		if (isset($_POST['offset'])) {
			$offset = $_POST['offset'];
		}
		if (isset($_POST['limit'])) {
			$limit = $_POST['limit'];
		}

		if (!empty($_POST['offset'])) {
			$followModel = $this->Storefollowers->find('all', array(
				'conditions' => array(
					'store_id' => $storeId,
				),
				'limit' => $limit,
				'offset' => $_POST['offset'],
			));

		} else {

			$followModel = $this->Storefollowers->find('all', array(
				'conditions' => array(
					'store_id' => $storeId,
				),
				'limit' => $limit,
			));
		}

		$followModel1 = $this->Followers->find()->where(['follow_user_id' => $loggeduserId])->all();//
		if (count($followModel->toArray()) > 0) {
			foreach ($followModel as $follower) {
				$followers[] = $follower['follow_user_id'];
			}

			foreach ($followModel1 as $follower1) {
				$followers_list[] = $follower1['user_id'];
			}

			$userModel = $this->Users->find()->where(['activation <>' => 0])->andWhere(['id IN' => $followers])->all();
			$resultArray = array();
			foreach ($userModel as $key => $value) {
				$resultArray[$key]['user_id'] = $value['id'];
				$resultArray[$key]['full_name'] = $value['first_name'] . ' ' . $value['last_name'];
				$resultArray[$key]['user_name'] = $value['username'];
				$imageName = $value['profile_image'];
				if ($imageName == '') {
					$imageName = "usrimg.jpg";
				}
				$resultArray[$key]['user_image'] = $img_path . 'media/avatars/thumb350/' . $imageName;

				if (in_array($value['id'], $followers_list)) {
					$resultArray[$key]['status'] = 'unfollow';
				} else {
					$resultArray[$key]['status'] = 'follow';
				}
			}

			$resultArray = json_encode($resultArray);
			echo '{"status":"true","result":' . $resultArray . '}';
			die;
		} else {
			echo '{"status":"false","message":"No Data Found"}';
			die;
		}
	}

	function pushNotificationRegister()
	{

		$this->loadModel('Userdevices');
		$deviceToken = $_POST['device_token'];
		$deviceId = $_POST['device_id'];
		$userId = $_POST['user_id'];

		$flag = 0;
		$pos = strpos($deviceToken, "aaa");
		if ($pos > 0) {
			$flag = 1;
		} elseif ($deviceToken == 'aaa') {
			$flag = 1;
		} else {
			$flag = 0;
		}

		if (!empty($_POST['device_id'])) {
			$mode = $_POST['device_mode'];
			$userdeviceDet = $this->Userdevices->find()->where(['deviceId' => $deviceId])->all();//all',array('conditions'=>array('Userdevice.deviceId'=>$deviceId)));
		} else {
			$userdeviceDet = $this->Userdevices->find()->where(['deviceToken' => $deviceToken])->all();
		}
		if (($_POST['device_type'] != "")) {

			if (count($userdeviceDet) != 0) {
				if ($flag == 1) {
					echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
					die;
				}
					/*if (isset($_POST['deviceId'])){
						$this->Userdevice->updateAll(array('Userdevice.user_id' => $userId,'Userdevice.type' => $_POST['devicetype']), array('Userdevice.deviceId' => $deviceId));
					}
					if (isset($_POST['devicetype'])){
						$this->Userdevice->updateAll(array('Userdevice.user_id' => $userId,'Userdevice.type' => $_POST['devicetype']));
					}*/
					if (!empty($_POST['device_token']) && $flag != 1) {

						//$this->Userdevice->updateAll(array('Userdevice.user_id' => $userId, 'Userdevice.deviceToken'=>"'$deviceToken'"));
						//$this->Userdevice->updateAll(array('Userdevice.user_id' => "'$userId'",'Userdevice.type' => "'$_POST['devicetype']'"), array('Userdevice.deviceToken'=>$deviceToken));
						$this->Userdevices->updateAll(array('user_id' => $userId, 'type' => $_POST['device_type']), array('deviceToken' => $deviceToken));

					}
				} else {
					$device_data = $this->Userdevices->newEntity();
					$device_data->user_id = $userId;
					if ($flag != 1) {
						$device_data->deviceToken = $deviceToken;
					} else {
						echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
						die;
					}
					if (!empty($_POST['device_id'])) {
						$device_data->deviceId = $deviceId;
					}
					if (!empty($_POST['device_type'])) {
						$device_data->type = $_POST['device_type'];
					}
					if (!empty($_POST['device_mode'])) {
						$device_data->mode = $_POST['device_mode'];
					}

					$device_data->cdate = time();
					$this->Userdevices->save($device_data);

				}
				echo '{"status":"true","result":"Registered successfully"}';
				die;
			} else {
				echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
				die;
			}
		}

		function pushNotificationUnregister()
		{

			$deviceId = $_POST['device_id'];
			$this->loadModel('Userdevices');
			if (!empty($deviceId) && trim($deviceId) != '') {
				if ($this->Userdevices->deleteAll(array('deviceId' => $deviceId), false)) {
					echo '{"status":"true","message":"Unregistered successfully"}';
					die;
				} else {
					echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
					die;
				}
			} else {
				echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
				die;
			}
		}

		function viewAllItems()
		{

			$userId = $_POST['user_id'];
			$offset = $_POST['offset'];
			$limit = $_POST['limit'];


			$type = $_POST['type'];
			$this->loadModel('Homepagesettings');
			$homepageModel = $this->Homepagesettings->find()->where(['layout' => 'custom'])->first();
			if ($type == "popular") {
				$all_items = $this->popularProducts($offset, $limit);
				$result = json_encode($all_items);
			} elseif ($type == "recent") {
				$all_items = $this->recentProducts($offset , $limit);
				$result = json_encode($all_items);

			} elseif ($type == "featured") {
				$all_items = $this->featuredProducts($offset , $limit);
				$result = json_encode($all_items);
			} elseif ($type == "deals") {
				$all_items = $this->dailyDeals($offset , $limit);
				$result = $all_items;
			} elseif ($type == "suggestitems") {
				$all_items = $this->suggestitem_viewmore($userId, $_POST['offset'], $_POST['limit']);
				$result = json_encode($all_items);
			} elseif ($type == "categories") {
				$all_items = $this->categoryProducts($homepageModel->categories, $_POST['offset'], $_POST['limit'], 'sfs',$userId);
				$result = json_encode($all_items);
				//print_r($all_items); die;
			} elseif ($type == "top_rated") {
				$all_items = $this->topRatedproducts($_POST['offset'], $_POST['limit']);
				$result = json_encode($all_items);
			} elseif ($type == "discounts") {
				//$all_items = ;
				$all_items = $this->discountProducts($_POST['offset'], $_POST['limit']);
				$result = json_encode($all_items);
			} else {
				echo '{"status":"false","message":"No data found"}';
				die;
			}

			if (count($all_items) == 0) {
				echo '{"status":"false","message":"No data found"}';
				die;
			} else {
				echo '{"status":"true","items":' . $result . '}';
				die;

			}

		}

		function likedItems()
		{

			$this->loadModel('Itemfavs');
			$this->loadModel('Items');
			//global $setngs;

			$favitems_ids = array();
			$items_data = array();
			$offset = 0;
			$limit = 10;
			if (isset($_POST['offset'])) {
				$offset = $_POST['offset'];
			}
			if (isset($_POST['limit'])) {
				$limit = $_POST['limit'];
			}
			if (!empty($_POST['user_id'])) {
				$userId = $_POST['user_id'];

				if (!empty($_POST['offset'])) {
					$items_fav_data = $this->Itemfavs->find('all', array(
						'conditions' => array(
							'user_id' => $userId,
						),
						'limit' => $limit,
						'offset' => $_POST['offset'],
						'order' => 'id DESC',

					));

				} else {

					$items_fav_data = $this->Itemfavs->find('all', array(
						'conditions' => array(
							'user_id' => $userId,
						),
						'order' => 'id DESC',
						'limit' => $limit,

					));
				}
			}

			if ($items_fav_data->count() > 0) {

				foreach ($items_fav_data as $favitems) {
					$favitems_ids = $favitems['item_id'];
					$items_data[] = TableRegistry::get('Items')->find()->contain('Forexrates')->where(['Items.id' => $favitems_ids])->first();
				}
			} else {
				echo '{"status":"false","message":"No data found"}';
				die;
			}

			if (!empty($_POST['logged_user_id'])) {
				$loggedUserFav = $this->Itemfavs->find()->where(['user_id' => $_POST['logged_user_id']])->all();
				if (count($loggedUserFav) > 0) {
					foreach ($loggedUserFav as $logfavitems) {
						$loggedUserFav_ids[] = $logfavitems['item_id'];
					}
				} else {
					$loggedUserFav_ids = array();
				}
			}

			$resultArray = $this->convertJsonHome($items_data, $loggedUserFav_ids, $_POST['logged_user_id']);
			if (count($resultArray) == 0) {
				echo '{"status":"false","message":"No data found"}';
				die;
			}
			echo '{"status":"true","result":' . json_encode($resultArray) . '}';
			die;
		}

		function profile()
		{
			$this->loadModel('Sitesettings');
			$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
			if (SITE_URL == $setngs[0]['media_url']) {
				$img_path = $setngs[0]['media_url'];
			} else {
				$img_path = $setngs[0]['media_url'];
			}
			$this->loadModel('Users');
			$this->loadModel('Followers');
			$this->loadModel('Shops');
			$this->loadModel('Storefollowers');
			$this->loadModel('Itemfavs');
			$this->loadModel('Itemlists');
			$user_id = $_POST['other_user_id'];
			$user_name = $_POST['user_name'];
			$username_url = $_POST['username_url'];
			$loggeduser_id = $_POST['logged_user_id'];

			if (!empty($user_id)) {
				$user_data = $this->Users->find()->where(['id' => $user_id])->first();
			}

			
			if (!empty($user_name)) {
				$user_data = $this->Users->find()->where(['username' => $user_name])->first();
			}

			if (!empty($username_url)) {
				$user_data = $this->Users->find()->where(['username_url' => $username_url])->first();
			}

			if (count($user_data) == 0) {
				echo '{"status": "false", "message": "No data found"}';
				die;
			}

			elseif ($user_data['user_status'] == "disable") {
				echo '{"status":"error","message":"The user has been blocked by admin"}';
				die;
			}

			if($user_data['user_level'] == 'shop')
			{
				$getShops = $this->Shops->find()->where(['user_id'=>$user_data['id']])->first();
				$resultarray['store_id'] = $getShops->id;
				$resultarray['shop_name_url'] = $getShops->shop_name_url;
				$resultarray['shop_name'] = $getShops->shop_name;
				$resultarray['shop_image'] = SITE_URL.'media/avatars/thumb70/'.$getShops->shop_image;
			}
			

			$resultarray['user_id'] = $user_data['id'];

			$resultarray['user_name'] = $user_data['username'];
			if(!empty($user_data['phone_no'])) {
				$resultarray['phone'] = $user_data['phone_no'];
			} else {
				$resultarray['phone'] = '0';
			}
			$resultarray['full_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
			$imageName = $user_data['profile_image'];
			if ($imageName == '') {
				$imageName = "usrimg.jpg";
			}
			$resultarray['user_image'] = $img_path . 'media/avatars/thumb350/' . $imageName;
			if ($user_data['password'] == "") {
				$resultarray['has_password'] = "no";
			} else {
				$resultarray['has_password'] = "yes";
			}

			$following = $this->Followers->find()->where(['follow_user_id' => $user_data['id']])->count();
			$resultarray['following'] = $following;
			$followers = $this->Followers->find()->where(['user_id' => $user_data['id']])->count();
			$resultarray['followers'] = $followers;
			$follow_stores = $this->Storefollowers->find()->where(['follow_user_id' => $user_data['id']])->count();
			$resultarray['follow_stores'] = $follow_stores;
			$liked_count = $this->Itemfavs->find()->where(['user_id' => $user_data['id']])->count();
			$resultarray['liked_count'] = $liked_count;

			$collection_count = $this->Itemlists->find()->where(['user_id' => $user_data['id']])->count();
			$resultarray['collection_count'] = $collection_count;

			$resultarray['credits'] = $user_data['credit_total'];

			$status = $this->Followers->find()->where(['user_id' => $user_id])->andWhere(['follow_user_id' => $loggeduser_id])->count();
			if($status == 0)
			{
				$resultarray['follow_status'] = 'follow';
			}
			else
			{
				$resultarray['follow_status'] = 'unfollow';
			}

			if (!empty($resultarray)) {
				$resultarray = json_encode($resultarray);
				echo '{"status":"true","result":' . $resultarray . '}';
				die;
			}
			else{
				echo '{"status": "false", "message": "No data found"}';
				die;
			}
		}

		function findFriends()
		{

			$this->loadModel('Users');
			$this->loadModel('Followers');

			$this->loadModel('Sitesettings');
			$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
			if (SITE_URL == $setngs[0]['media_url']) {
				$img_path = $setngs[0]['media_url'];
			} else {
				$img_path = $setngs[0]['media_url'];
			}

			$userId = $_POST['user_id'];

			$userNameKey = $_POST['search_key'];

			$offset = 0;
			$limit = 10;
			if (isset($_POST['offset'])) {
				$offset = $_POST['offset'];
			}
			if (isset($_POST['limit'])) {
				$limit = $_POST['limit'];
			}

			$followModel = $this->Followers->find()->where(['follow_user_id' => $userId])->all();

			if (count($followModel) > 0) {
				foreach ($followModel as $follower) {
					$followers[] = $follower['user_id'];
				}
			}

			if (!empty($userNameKey)) {
				if ($_POST['user_id'] != "")
					$condition = array('username LIKE' => "%" . $userNameKey . "%", 'activation' => 1, 'user_status' => 'enable', 'user_level' => 'normal', 'id <>' => $userId);
				else
					$condition = array('username LIKE' => "%" . $userNameKey . "%", 'activation' => 1, 'user_status' => 'enable', 'user_level' => 'normal');

				if (!empty($_POST['offset'])) {
					$items_data = $this->Users->find('all', array(
						'conditions' => $condition,

						'limit' => $limit,
						'offset' => $_POST['offset'],
						'order' => 'id DESC',

					));

				} else {

					$items_data = $this->Users->find('all', array(
						'conditions' => $condition,

						'limit' => $limit,
						'order' => 'id DESC',
					));
				}
			} else {
				if ($_POST['user_id'] != "")
					$condition = array('activation' => 1, 'user_status' => 'enable', 'user_level' => 'normal', 'id <>' => $userId);
				else
					$condition = array('activation' => 1, 'user_status' => 'enable', 'user_level' => 'normal');

				if (!empty($_POST['offset'])) {
					$items_data = $this->Users->find('all', array(
						'conditions' => $condition,

						'limit' => $limit,
						'offset' => $_POST['offset'],
						'order' => 'id DESC',

					));

				} else {

					$items_data = $this->Users->find('all', array(
						'conditions' => $condition,

						'limit' => $limit,
						'order' => 'id DESC',
					));
				}
			}

			foreach ($items_data as $key => $listitem) {
				$resultArray[$key]['user_id'] = $listitem['id'];

				$resultArray[$key]['full_name'] = $listitem['first_name'] . ' ' . $listitem['last_name'];
				$resultArray[$key]['user_name'] = $listitem['username'];
				$imageName = $listitem['profile_image'];
				if ($imageName == '') {
					$imageName = "usrimg.jpg";
				}
				$resultArray[$key]['user_image'] = $img_path . 'media/avatars/thumb350/' . $imageName;
				if (in_array($listitem['id'], $followers)) {
					$resultArray[$key]['status'] = 'Unfollow';
				} else {
					$resultArray[$key]['status'] = 'follow';
				}

			}

			//echo "<pre>";print_r($resultArray);die;
			if (isset($resultArray)) {
				echo '{"status":"true","result":' . json_encode($resultArray) . '}';
				die;
			} else {
				echo '{"status":"false","message":"No data found"}';
				die;
			}
		}

		function groupGiftList()
		{
			$this->loadModel('Groupgiftuserdetails');
			$userid = $_POST['user_id'];
			$offset = 0;
			$limit = 10;
			if (isset($_POST['offset'])) {
				$offset = $_POST['offset'];
			}
			if (isset($_POST['limit'])) {
				$limit = $_POST['limit'];
			}
			if (!empty($_POST['offset'])) {
				$groupgifts = $this->Groupgiftuserdetails->find('all', array(
					'conditions' => array(
						'user_id' => $userid,
					),
					'limit' => $limit,
					'offset' => $_POST['offset'],
					'order' => 'id DESC',

				));
			} else {

				$groupgifts = $this->Groupgiftuserdetails->find('all', array(
					'conditions' => array(
						'user_id' => $userid,
					),

					'limit' => $limit,
					'order' => 'id DESC',
				));
			}
			foreach ($groupgifts as $key => $gifts) {
				$ggid = $gifts['id'];
				$resultarray[$key]['gift_id'] = $gifts['id'];

				$resultarray[$key]['start_date'] = $gifts['c_date'];
				$resultarray[$key]['end_date'] = $gifts['c_date'] + 604800;
				if ($resultarray[$key]['end_date'] > time()) {
					if ($gifts['status'] == 'Completed') {
						$resultarray[$key]['status'] = 'Completed';
					}
					 else {
						$resultarray[$key]['status'] = 'active';
					}
				}
				else if ($gifts['status'] == 'Refunded') {
						$resultarray[$key]['status'] = 'Refunded';
					}
				else {
					$resultarray[$key]['status'] = 'expired';
				}
				$resultarray[$key]['title'] = $gifts['title'];
			}
			if (empty($resultarray)) {
				echo '{"status":"false","message":"No gift found"}';
				die;
			}
			echo '{"status":"true","result":' . json_encode($resultarray) . '}';
			die;

		}

		/** GROUPGIFT DETAILS */
		function groupGiftDetail()
		{
			$this->loadModel('Groupgiftuserdetails');
			$this->loadModel('Groupgiftpayamts');
			$this->loadModel('Items');
			$this->loadModel('Users');
			$this->loadModel('Countries');
			$this->loadModel('Forexrates');
			$this->loadModel('Photos');
			$userid = $_POST['user_id'];
			$giftid = $_POST['gift_id'];
			$user_data = $this->Users->find()->where(['id' => $userid])->first();
			$setngs = $this->Sitesettings->find()->toArray();
			if (SITE_URL == $setngs[0]['media_url']) {
				$img_path = $setngs[0]['media_url'];
			} else {
				$img_path = $setngs[0]['media_url'];
			}
			//$gifts = $this->Groupgiftuserdetails->find()->where(['Groupgiftuserdetails.user_id'=>$userid])->where(['Groupgiftuserdetails.id'=>$giftid])->first();
			$gifts = $this->Groupgiftuserdetails->find()->where(['Groupgiftuserdetails.id' => $giftid])->first();
			if (count($gifts) != 0) {
				$ggid = $gifts['id'];
				$resultarray['gift_id'] = $gifts['id'];
				$resultarray['recipient_name'] = $gifts['name'];
				if ($gifts['image'] == "")
					$recipientimage = "usrimg.jpg";
				else
					$recipientimage = $gifts['image'];
				$resultarray['recipient_image'] = $img_path . 'media/avatars/thumb150/' . $recipientimage;
				$resultarray['start_date'] = $gifts['c_date'];
				$resultarray['end_date'] = $gifts['c_date'] + 604800;
				if ($resultarray['end_date'] > time())
					$resultarray['status'] = 'active';
				else
					$resultarray['status'] = 'expired';
				$itemid = $gifts['item_id'];
				$item_data = $this->Items->find()->where(['Items.id' => $itemid])->first();

				$photo = $this->Photos->find()->where(['item_id' => $item_data['id']])->first();

				if ($user_data['currencyid'] == 0 || $user_data['currencyid'] == "") {
					$currency_data = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();
				} else {
					$currency_data = $this->Forexrates->find()->where(['id' => $user_data['currencyid']])->first();
				}

				/*CURRENCY CONVERSION */
				$convertFromCurrency = $this->Forexrates->find()->where(['id' => $gifts['currencyid']])->first();

				$countryId = $gifts['country'];
				$countryDetails = $this->Countries->find()->where(['id' => $countryId])->first();
				$countryName = $countryDetails['country'];

				$resultarray['currency'] = $currency_data['currency_symbol'];
				$resultarray['currency_code'] = $currency_data['currency_code'];
				$gifts['total_amt'] = $this->Currency->conversion($convertFromCurrency['price'], $currency_data['price'], $gifts['total_amt']);
				$resultarray['total_contribution'] = $gifts['total_amt'];
				$resultarray['minimum_contribution'] = round((($resultarray['total_contribution'] * 5) / 100), 2);

				$payment = $this->Groupgiftpayamts->find()->where(['Groupgiftpayamts.ggid' => $ggid])->all();
				$i = 0;
				$paidcontribution = 0;
				foreach ($payment as $pay) {
					$paidcontribution += $pay['amount'];
					$i++;
				}
				$user_id = $gifts['user_id'];
				$userdata = $this->Users->find()->where(['id' => $user_id])->first();
				$paidcontribution = $this->Currency->conversion($convertFromCurrency['price'], $currency_data['price'], $paidcontribution);
				$resultarray['paid_contribution'] = $paidcontribution;
				$resultarray['total_contributors'] = $i;
				$resultarray['share_url'] = SITE_URL . 'gifts/' . $ggid;
				$resultarray['title'] = $gifts['title'];
				$resultarray['description'] = $gifts['description'];
				$resultarray['creator_id'] = $gifts['user_id'];
				$resultarray['creator_name'] = $userdata['username'];
				if ($userdata['profile_image'] == "")
					$userimage = "usrimg.jpg";
				else
					$userimage = $userdata['profile_image'];
				$resultarray['creator_image'] = $img_path . 'media/avatars/thumb150/' . $userimage;
				$resultarray['merchant_id'] = $item_data['user_id'];
				$resultarray['address']['address1'] = $gifts['address1'];
				$resultarray['address']['address2'] = $gifts['address2'];
				$resultarray['address']['city'] = $gifts['city'];
				$resultarray['address']['state'] = $gifts['state'];
				$resultarray['address']['country'] = $countryName;
				$resultarray['product']['item_id'] = $item_data['id'];
				$resultarray['product']['item_name'] = $item_data['item_title'];
				$resultarray['product']['item_image'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];
				foreach ($payment as $keys => $pay) {
					$contributorId = $pay['paiduser_id'];
					$contributordata = $this->Users->find()->where(['id' => $contributorId])->first();
					$contributorimage = $contributordata['profile_image'];
					if ($contributorimage == "")
						$contributorimage = "usrimg.jpg";
					$resultarray['contributors'][$keys]['user_id'] = $pay['paiduser_id'];
					$resultarray['contributors'][$keys]['user_name'] = $contributordata['username'];
					$resultarray['contributors'][$keys]['user_image'] = $img_path . 'media/avatars/thumb150/' . $contributorimage;
					$resultarray['contributors'][$keys]['amount_contributed'] = $this->Currency->conversion($convertFromCurrency['price'], $currency_data['price'], $pay['amount']);
				}

				echo '{"status":"true","result":' . json_encode($resultarray) . '}';
				die;
			} else {
				echo '{"status":"false","message":"No gift found"}';
				die;
			}
		}

		/*  CREATE GROUPGIFT */
		function createGroupGift()
		{
			$this->loadModel('Groupgiftuserdetails');
			$this->loadModel('Items');
			$this->loadModel('Users');
			$this->loadModel('Countries');
			$this->loadModel('Photos');
			$this->loadModel('Forexrates');
			$this->loadModel('Shippingaddresses');
			$this->loadModel('Shipings');
			$this->loadModel('Shops');
			$user_id = $_POST['user_id'];
			$item_id = $_POST['item_id'];
			$size = $_POST['size'];
			$quantity = $_POST['quantity'];
			$recipient = $_POST['recipient'];
			$name = $_POST['full_name'];
			$address1 = $_POST['address1'];
			$address2 = $_POST['address2'];
			$country = $_POST['country_id'];
			$state = $_POST['state'];
			$city = $_POST['city'];
			$zipcode = $_POST['zipcode'];
			$telephone = $_POST['phone'];
			$title = $_POST['title'];
			$description = $_POST['description'];
			$notes = $_POST['note'];
			/* Received Totals*/
			$rec_item_total = $_POST['item_total'];
			$rec_shipping_price = $_POST['shipping_price'];
			$rec_tax = $_POST['tax'];
			$rec_grand_total = $_POST['grand_total'];
			/* Received Totals*/
			$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();
			$userDetail = $this->Users->find()->where(['id' => $user_id])->first();
			$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();
			if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "" || count($userDetail) == 0) {
				$cur_symbol = $forexrateModel['currency_symbol'];
				$cur = $forexrateModel['price'];
			} else {
				$cur_symbol = $currency_value['currency_symbol'];
				$cur = $currency_value['price'];
			}

			$setngs = $this->Sitesettings->find()->toArray();
			if (SITE_URL == $setngs[0]['media_url']) {
				$img_path = $setngs[0]['media_url'];
			} else {
				$img_path = $setngs[0]['media_url'];
			}

			$groupgiftdetails = $this->Groupgiftuserdetails->newEntity();
			$groupgiftdetails->user_id = $user_id;
			$groupgiftdetails->item_id = $item_id;
			$groupgiftdetails->recipient = $recipient;
			$groupgiftdetails->name = $name;
			$groupgiftdetails->address1 = $address1;
			$groupgiftdetails->address2 = $address2;
			$groupgiftdetails->country = $country;
			$groupgiftdetails->state = $state;
			$groupgiftdetails->city = $city;
			$groupgiftdetails->zipcode = $zipcode;
			$groupgiftdetails->telephone = $telephone;
			$groupgiftdetails->c_date = time();
			$groupgiftdetails->status = 'Active';
			$groupgiftdetails->title = $title;
			$groupgiftdetails->description = $description;
			$groupgiftdetails->notes = $notes;
			$groupgiftdetails->currencyid = $userDetail['currencyid'];
			$item_datas = TableRegistry::get('Items')->find()->contain('Forexrates')->where(['Items.id' => $item_id, ['Items.status' => 'publish']])->andWhere(['Items.quantity <>' => 0])->first();

			if (empty($item_datas)) {
				echo '{"status":"false","message":"Item not found"}';
				die;
			}
			$receiverdata = $this->Users->find()->where(['email' => $recipient])->first();
			$usernameaddrdetails = $this->Shippingaddresses->find()->where(['userid' => $receiverdata['id']])->first();
			$cntry_datas = $this->Countries->find()->all();
			$cntryids[] = "";
			foreach ($cntry_datas as $cntrydatas) {
				$cntryids[] = $cntrydatas['id'];
			}

			if (!in_array($country, $cntryids)) {
				echo '{"status":"false","message":"Shipping can not be done"}';
				die;
			}

			$shiping = TableRegistry::get('Shipings')->find()->where(['item_id' => $item_id])->andwhere(['country_id' => $country])->first();

			$primary_cost = $shiping['primary_cost'];

			$everywhereshipping = TableRegistry::get('Shipings')->find()->where(['item_id' => $item_id])->andwhere(['country_id' => '0'])->first();

			if (count($everywhereshipping) > 0 && count($shiping) == 0) {
				$primary_cost = $everywhereshipping['primary_cost'];
			}

			if (count($shiping) == 0 && count($everywhereshipping) == 0) {
				echo '{"status":"false","message":"Shipping can not be done"}';
				die;
			}
			$shipping_amt = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $primary_cost);
			$sizeoptions = $item_datas['size_options'];
			$sizes = json_decode($sizeoptions, true);

			if (!empty($sizes) && $size != "") {
				$sizeoptions = $item_datas['size_options'];
				$sizes = json_decode($sizeoptions, true);
				$price = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $sizes['price'][$size]);
				$itemtotal = $price * $quantity;
				$itemPrice = $price;

			} else {
				$price = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $item_datas['price']);
				$itemtotal = $price * $quantity;
				$itemPrice = $price;

			}
			$this->loadModel('Taxes');
			$tax_datas = $this->Taxes->find()->where(['countryid' => $country])->andWhere(['status' => 'enable'])->all();
			foreach ($tax_datas as $taxes) {
				$tax_perc += $taxes['percentage'];
			}
			$tax = ($itemtotal * $tax_perc) / 100;
			$groupgiftdetails->itemcost = $rec_item_total;
			if ($size != 'null' && $size != "") {
				$groupgiftdetails->itemsize = $size;
			} else {
				$groupgiftdetails->itemsize = '';
			}
			$groupgiftdetails->itemquantity = $quantity;
			$TotalCost = round(($itemPrice * $quantity), 2);
			$groupgiftdetails->shipcost = $rec_shipping_price;
			$groupgiftdetails->tax = $rec_tax;
			$totals_amt = $itemtotal + $shipping_amt + $tax;
			$groupgiftdetails->total_amt = round($rec_grand_total, 2);
			$groupgiftdetails->balance_amt = round($rec_grand_total, 2);
			$result = $this->Groupgiftuserdetails->save($groupgiftdetails);
			$lasttId = $result->id;
		$item_data = $this->Items->find()->where(['id' => $item_id])->first();//ById($item_id);
		$tot_qnty = $item_data['quantity'];
		$rem_qnty = $tot_qnty - $quantity;
		$sizeoptions = $item_data['size_options'];
		$sizes = json_decode($sizeoptions, true);
		if (!empty($sizes)) {
			$sizeoptions = $item_data['size_options'];
			$sizes = json_decode($sizeoptions, true);
				//echo $size;
			$sizes['unit'][$size] = $sizes['unit'][$size] - $quantity;
			$size_options = json_encode($sizes);
			$this->Items->updateAll(array('quantity' => $rem_qnty, 'size_options' => $size_options), array('id' => $item_id));
		} else
		$this->Items->updateAll(array('quantity' => $rem_qnty), array('id' => $item_id));
		$image = array();
		$loguser = $this->Users->find()->where(['id' => $user_id])->first();//ById($userId);
		$logusrid = $loguser['id'];
		$item_user_id = $item_datas['user_id'];
		$logusername = $loguser['username'];
		$logusernameurl = $loguser['username_url'];
		$userDesc = "";
		$userImg = $loguser['profile_image'];
		if (empty($userImg)) {
			$userImg = 'usrimg.jpg';
		}
		$image['user']['image'] = $userImg;
		$image['user']['link'] = SITE_URL . "people/" . $logusernameurl;
		$loguserimage = json_encode($image);
		$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logusername . "</a>";
		$giftlink = '<a href="' . SITE_URL . 'gifts/' . $lasttId . '">' . $lasttId . '</a>';
		$notifymsg = $loguserlink . " -___-Created a group gift on your product, Group Gift Id :" . $giftlink;
		$logdetails = $this->addlog('groupgift', $logusrid, $item_user_id, 0, $notifymsg, $userDesc, $loguserimage);

		$groupgiftdetails = $this->Groupgiftuserdetails->find()->where(['id' => $lasttId])->first();//ById($lasttId);
		$resultarray['gift_id'] = $lasttId;
		$resultarray['recipient_name'] = $groupgiftdetails['name'];
		$resultarray['city'] = $groupgiftdetails['city'];
		$resultarray['share_url'] = SITE_URL . 'gifts/' . $groupgiftdetails['id'];
		$resultarray['title'] = $groupgiftdetails['title'];
		$resultarray['description'] = $groupgiftdetails['description'];
		$resultarray['item_name'] = $item_datas['item_title'];
		$photo = $this->Photos->find()->where(['item_id' => $item_datas['id']])->first();
		$currency_data = $this->Forexrates->find()->where(['id' => $item_datas['currencyid']])->first();
		$resultarray['item_image'] = $img_path . 'media/items/thumb150/' . $photo['image_name'];
		$resultarray['currency'] = $cur_symbol;
		$resultarray['item_total'] = $groupgiftdetails['itemcost'] * $quantity;
		$resultarray['shipping_price'] = $groupgiftdetails['shipcost'];
		$resultarray['tax'] = $groupgiftdetails['tax'];
		$resultarray['grand_total'] = $groupgiftdetails['total_amt'];
		if (!empty($groupgiftdetails)) {
			echo '{"status":"true","result":' . json_encode($resultarray) . '}';
			die;
		} else {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		}
	}

	public function addlog($type = null, $userId = null, $notifyTo = null, $sourceId = null, $notifymsg = null, $message = null, $image = null, $itemid = 0)
	{

		$this->loadModel('Logs');
		$this->loadModel('Users');

		$log_detail = $this->Logs->newEntity();
		$log_detail->type = $type;
		$log_detail->userid = $userId;
		$log_detail->notifyto = 0;
		if (!is_array($notifyTo)) {
			$log_detail->notifyto = $notifyTo;
		}
		$log_detail->sourceid = $sourceId;
		$log_detail->itemid = $itemid;
		$log_detail->notifymessage = $notifymsg;
		$log_detail->message = $message;
		$log_detail->image = $image;
		$log_detail->cdate = time();
		$this->Logs->save($log_detail);

		$loguser = $this->Users->find()->where(['id IN' => $notifyTo])->all();
		foreach ($loguser as $logusers){
			if($type=="mentioned" || $type=="additem" || $type=="comment" || $type=="status" || 
				$type=="favorite") {
				$unread_livefeed_cnt = $logusers['unread_livefeed_cnt'] + 1;
			$user_notification_count = array('unread_livefeed_cnt' => $unread_livefeed_cnt);
		}
		else{
			$unread_notify_cnt = $logusers['unread_notify_cnt'] + 1;
			$user_notification_count = array('unread_notify_cnt' => $unread_notify_cnt);
		}
		$this->Users->updateAll($user_notification_count, array('id' => $logusers['id']));
	}

}

function groupGiftPaymentdetail()
{

	$this->loadModel('Groupgiftuserdetails');
	$this->loadModel('Items');
	$this->loadModel('Users');
	$this->loadModel('Taxes');
	$this->loadModel('Countries');
	$this->loadModel('Photos');
	$this->loadModel('Forexrates');
	$this->loadModel('Shippingaddresses');
	$this->loadModel('Shipings');
	$this->loadModel('Shops');
	$user_id = $_POST['user_id'];
	$zipcode = $_POST['zipcode'];

	$item_id = $_POST['item_id'];
	$size = $_POST['size'];
	$quantity = $_POST['quantity'];

	$country = $_POST['country_id'];

	$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();
	$userDetail = $this->Users->find()->where(['id' => $user_id])->first();
	$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();
	if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "" || count($userDetail) == 0) {
		$cur_symbol = $forexrateModel['currency_symbol'];
		$cur = $forexrateModel['price'];
	} else {
		$cur_symbol = $currency_value['currency_symbol'];
		$cur = $currency_value['price'];
	}

	$setngs = $this->Sitesettings->find()->toArray();
	if (SITE_URL == $setngs[0]['media_url']) {
		$img_path = $setngs[0]['media_url'];
	} else {
		$img_path = $setngs[0]['media_url'];
	}

	$item_datas = TableRegistry::get('Items')->find()->contain('Forexrates')->where(['Items.id' => $item_id, ['Items.status' => 'publish']])->andWhere(['Items.quantity <>' => 0])->first();
	if (empty($item_datas)) {
		echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
		die;
	}

		//	$condition = array('user_id'=>$user_id,'item_id'=>$item_id,'itemsize'=>$size,'quantity'=>$quantity,'country'=>$country);

		//$groupgiftdetails = $this->Groupgiftuserdetails->find()->where(['user_id'=>$user_id,['item_id'=>$item_id,['itemsize'=>$size]]])->andWhere(['itemquantity'=>$quantity,['country'=>$country]])->first();

	$user_data = $this->Users->find()->where(['id' => $user_id])->first();

	$resultarray['item_name'] = $item_datas['item_title'];
	$photo = $this->Photos->find()->where(['item_id' => $item_datas['id']])->first();
	$currency_data = $this->Forexrates->find()->where(['id' => $user_data['currencyid']])->first();
	$resultarray['item_image'] = $img_path . 'media/items/thumb150/' . $photo['image_name'];
	$resultarray['currency'] = $cur_symbol;

	if ($currency_data['cstatus'] == "default") {
		if ($size == "") {

			$price = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $item_datas['price']);
			$resultarray['item_total'] = $price * $quantity;

		} else {
			$sizeoptions = $item_datas['size_options'];
			$sizes = json_decode($sizeoptions, true);
			if (!empty($sizes)) {
				$sizeoptions = $item_datas['size_options'];
				$sizes = json_decode($sizeoptions, true);
						//echo $size;
				$price = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $sizes['price'][$size]);

				$resultarray['item_total'] = $price * $quantity;

			}
		}
	} else {
		if ($size == "") {

			$price = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $item_datas['price']);
			$resultarray['item_total'] = $price * $quantity;

		} else {
			$sizeoptions = $item_datas['size_options'];
			$sizes = json_decode($sizeoptions, true);
			if (!empty($sizes)) {
				$sizeoptions = $item_datas['size_options'];
				$sizes = json_decode($sizeoptions, true);

				$price = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $sizes['price'][$size]);

				$resultarray['item_total'] = $price * $quantity;

			}
		}

	}

	$shipings = $this->Shipings->find()->where(['country_id' => $country])->andWhere(['item_id' => $item_id])->first();

	/* Everywhere else shipping */
	if (count($shipings) == 0) {
		$shipings = $this->Shipings->find()->where(['country_id' => '0'])->andWhere(['item_id' => $item_id])->first();
		if (count($shipings) == 0) {
			echo '{"status":"false","message":"Shipping not possible"}';
			die;
		}
	}

	/* Seller Free shipping analysis */
	$shop_data = $this->Shops->find()->where(['id' => $item_datas['shop_id']])->first();
	$postalcode = json_decode($shop_data['postalcodes'], true);
	$Totalcost = $resultarray['item_total'];

	$shopCurrencyDetails = $this->Forexrates->find()->where(['currency_code' => $shop_data['currency']])->first();
	$totalfreeamt = $this->Currency->conversion($shopCurrencyDetails['price'], $cur, $shop_data['freeamt']);

	if (in_array($zipcode, $postalcode)) {
		$shipping_amt = 0;
	} else if ($Totalcost >= $totalfreeamt && $shop_data['pricefree'] == 'yes') {
		$shipping_amt = 0;
	} else {
		$shipping_amt = $shipings['primary_cost'];
	}
	$resultarray['shipping_price'] = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $shipping_amt);

	$tax_datas = $this->Taxes->find()->where(['countryid' => $country])->andWhere(['status' => 'enable'])->all();

	foreach ($tax_datas as $taxes) {
		$tax_perc += $taxes['percentage'];
	}

	$tax = round(($resultarray['item_total'] * $tax_perc) / 100, 2);

			//	$tax= $this->Taxes->find()->where(['countryid'=>$country])->first();
	if (count($tax) == 0) {
		$resultarray['tax'] = "";
	} else {
		$resultarray['tax'] = $tax;
	}

	$resultarray['grand_total'] = $resultarray['tax'] + $resultarray['shipping_price'] + $resultarray['item_total'];

				//$resultarray['shippingPossible'] = "true";

				//if(count($groupgiftdetails)!=0)
			//	{
	echo '{"status":"true","result":' . json_encode($resultarray) . '}';
	die;
			//	}
			//	else
			//	{
					//echo '{"status":"false","message":"Something went to be wrong"}';die;
			//	}
}

function sendSellermessage()
{

	$this->loadModel('Users');
	$this->loadModel('Contactsellers');
	$this->loadModel('Contactsellermsgs');
	$this->loadModel('Photos');
	$this->loadModel('Items');
	$this->loadModel('Shops');

	$setngs = $this->Sitesettings->find()->toArray();
	if (SITE_URL == $setngs[0]['media_url']) {
		$img_path = $setngs[0]['media_url'];
	} else {
		$img_path = $setngs[0]['media_url'];
	}
	$buyerId = $_POST['user_id'];

	$itemId = $_POST['item_id'];
	$shopId = $_POST['shop_id'];
	$chatId = $_POST['chat_id'];
	$subject = $_POST['subject'];
	$message = $_POST['message'];
			//$message = mb_convert_encoding($message,'UTF-8','HTML-ENTITIES');
	header("Content-Type: text/html; charset=UTF-8");
	$message = urldecode($message);
	$subject = urldecode($subject);

		/*	$loguserid = $loguser[0]['User']['id'];
			if($loguserid==$buyerId)
				$sender = "buyer";
			else if($loguserid==$merchantId)
			$sender = "seller";*/

			$sender = "buyer";
			$timenow = time();

		$item_datas = $this->Items->find()->where(['id' => $itemId])->first();//Byid($itemId);
		$user_datas = $this->Users->find()->where(['id' => $buyerId])->first();
		$store_datas = $this->Shops->find()->where(['id' => $shopId])->first();
		$merchantId = $store_datas['user_id'];

			/*if($buyerId==$merchantId)
			{
					$sender = "seller";
				} */
				$itemName = $item_datas['item_title'];
				$username = $user_datas['username'];
				$seller_datas = $this->Users->find()->where(['id' => $merchantId])->first();

				$sellername = $seller_datas['username'];

				if ($itemId != "" && $merchantId != "" && $buyerId != "" && $subject != "" && $message != "") {
				//echo "if";die;
					if (count($seller_datas) == 0) {
						echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
						die;
					}
					$contactseller_datas = $this->Contactsellers->find()->where(['itemid' => $itemId, ['merchantid' => $merchantId]])->andWhere(['buyerid' => $buyerId, ['subject' => $subject]])->first();

					$contactSellerLog = 0;
					if (count($contactseller_datas) == 0) {
						$contact_seller_datas = $this->Contactsellers->newEntity();
						$contact_seller_datas->itemid = $itemId;
						$contact_seller_datas->merchantid = $merchantId;
						$contact_seller_datas->buyerid = $buyerId;
						$contact_seller_datas->subject = $subject;
						$contact_seller_datas->itemname = $itemName;
						$contact_seller_datas->buyername = $username;
						$contact_seller_datas->sellername = $sellername;
						$contact_seller_datas->lastsent = $sender;
						if ($sender == 'buyer') {
							$contact_seller_datas->sellerread = 1;
							$contact_seller_datas->buyerread = 0;
						} else {
							$contact_seller_datas->sellerread = 0;
							$contact_seller_datas->buyerread = 1;
						}
						$contact_seller_datas->lastmodified = $timenow;
						$result = $this->Contactsellers->save($contact_seller_datas);

						$lastInserId = $result->id;
						$contactSellerLog = 1;
					} else {
						$contactseller_datas->lastmodified = $timenow;
						$contactseller_datas->buyerread = 0;
						$contactseller_datas->sellerread = 1;
						$contactseller_datas->lastsent = "buyer";
						$result = $this->Contactsellers->save($contactseller_datas);

						$lastInserId = $contactseller_datas['id'];
					}
				} elseif ($buyerId != "" && $subject != "" && $message != "" && $chatId != "") {
				//echo "elseif";
				//$contactseller_datas = $this->Contactsellers->find()->where(['id'=>$chatId,['merchantid'=>$buyertId]])->orWhere(['buyerid'=>$buyerId,['subject'=>$subject]])->first();
					$contactseller_datas = $this->Contactsellers->find()->where(['id' => $chatId, ['buyerid' => $buyerId]])->andWhere(['subject' => $subject])->first();
				//print_r($contactseller_datas);die;
					if (count($contactseller_datas) != 0) {
						$contactseller_datas->lastmodified = $timenow;
						$contactseller_datas->buyerread = 0;
						$contactseller_datas->sellerread = 1;
						$contactseller_datas->lastsent = "buyer";
						$result = $this->Contactsellers->save($contactseller_datas);

						$lastInserId = $chatId;
					} else {
						echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
						die;
					}

				} else {
					echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
					die;
				}
				$contact_seller_mesage = $this->Contactsellermsgs->newEntity();
				$contact_seller_mesage->contactsellerid = $lastInserId;
				$contact_seller_mesage->message = $message;
				$contact_seller_mesage->sentby = $sender;
				$contact_seller_mesage->createdat = $timenow;
				$this->Contactsellermsgs->save($contact_seller_mesage);

				if ($contactSellerLog == 1) {
					$itemImage = $this->Photos->find()->where(['item_id' => $itemId])->first();
					$loguser[0] = $user_datas;
					$logusername = $loguser[0]['username'];
					$logusernameurl = $loguser[0]['username_url'];
					$itemname = $itemName;
					//$itemurl = $this->Urlfriendly->utils_makeUrlFriendly($itemname);
					$userImg = $loguser[0]['profile_image'];
					if (empty($userImg)) {
						$userImg = 'usrimg.jpg';
					}
					$image['user']['image'] = $userImg;
					$image['user']['link'] = SITE_URL . "people/" . $logusernameurl;
					$image['item']['image'] = $itemImage['Photo']['image_name'];
					$image['item']['link'] = SITE_URL . "listing/" . $itemId . "/" . $itemurl;
					$loguserimage = json_encode($image);
					$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logusername . "</a>";
					$productlink = "<a href='" . SITE_URL . "listing/" . $itemId . "/" . $itemurl . "'>" . $itemname . "</a>";
					$notifymsg = $loguserlink . " -___-sent a query on your product: -___- " . $productlink;
					$logdetails = $this->addlog('chatmessage', $buyerId, $merchantId, $lastInserId, $notifymsg, $message, $loguserimage, $itemId);

					/*App::import('Controller', 'Users');
					$Users = new UsersController;
					$this->loadModel('Userdevice');
					$userddett = $this->Userdevice->find('all',array('conditions'=>array('user_id'=>$merchantId)));
					//echo "<pre>";print_r($userddett);die;
					foreach($userddett as $userdet){
						$deviceTToken = $userdet['Userdevice']['deviceToken'];
						$badge = $userdet['Userdevice']['badge'];
						$badge +=1;
						$this->Userdevice->updateAll(array('badge' =>"'$badge'"), array('deviceToken' => $deviceTToken));
						if(isset($deviceTToken)){
							$messages = $logusername." sent a query on your product ".$itemName;
							$Users->pushnot($deviceTToken,$messages,$badge);
						}
					}*/
				}

				$imageName = $loguser[0]['profile_image'];
				if ($imageName == '') {
					$imageName = "usrimg.jpg";
				}

				$resultarray['message'] = $message;
				$resultarray['user_name'] = $user_datas['username'];
				$resultarray['user_id'] = $user_datas['id'];
				$resultarray['user_image'] = $img_path . 'media/avatars/thumb350/' . $imageName;

				echo '{"status":"true","result":' . json_encode($resultarray) . '}';
				die;
			//}
			//else
		//	{
		//		echo '{"status":"false","result":"Something went to be wrong"}'; die;
		//	}
			}

			function getCollection()
			{
				if (!empty($_POST['user_id'])) {
					$this->loadModel('Itemlists');
					$this->loadModel('Categories');
					$userId = $_POST['user_id'];
					$itemId = $_POST['item_id'];
					$itemListModel = $this->Itemlists->find()->where(['user_id' => $userId])->order(['id DESC'])->all();
					$result = array();
					$key = 0;
					if (count($itemListModel) != 0) {
						foreach ($itemListModel as $key => $itemList) {
							$result[$key]['collection_id'] = $itemList['id'];
							$result[$key]['collection_name'] = $itemList['lists'];

							$result[$key]['type'] = $itemList['user_create_list'];
							$items = json_decode($itemList['list_item_id'], true);
							if (!empty($items) && in_array($itemId, $items)) {
								$result[$key]['checked'] = 1;
							} else {
								$result[$key]['checked'] = 0;
							}
							if ($itemList['user_create_list'] == 0) {
								$userDefineList[] = $itemList['lists'];
							}
						}
						$key = $key + 1;
					}
					$responce = json_encode($result);
					echo '{"status":"true","result":' . $responce . '}';
					die;
				} else {
					echo '{"status":"false","message":"No Data Found"}';
					die;
				}
			}

			function updateCollectionItems()
			{

				$this->loadModel('Itemlists');
				$itemId = $_POST['item_id'];
				$collectionId = $_POST['collection_id'];
				$checked = $_POST['checked'];
				$list_data = $this->Itemlists->find()->where(['id' => $collectionId])->first();
				if (count($list_data) != 0) {
					if ($checked == 1) {
						$jsonItemList = json_decode($list_data['list_item_id'], true);
						$inArray = in_array($itemId, $jsonItemList);
						if (!$inArray) {
							$jsonItemList[] = $itemId;
							$jsonItemList = json_encode($jsonItemList);
							$this->Itemlists->updateAll(array('list_item_id' => $jsonItemList), array('id' => $collectionId));
							//$this->request->data['Itemlist']['list_item_id'] = $jsonItemList;
						}

					} else {
						$jsonItemList = json_decode($list_data['list_item_id'], true);
						foreach ($jsonItemList as $key => $value) {
							if ($value == $itemId) {
								unset($jsonItemList[$key]);
							}
						}
						$jsonItemList = json_encode($jsonItemList);
						$this->Itemlists->updateAll(array('list_item_id' => $jsonItemList), array('id' => $collectionId));

					}
					echo '{"status":"true","message":"Collection Updated"}';
					die;

				} else {
					echo '{"status":"false","message":"Unable to update collection"}';
					die;
				}
			}
			function createCollection()
			{
				$this->loadModel('Itemlists');
				$this->loadModel('Categories');
				$userId = $_POST['user_id'];
				$itemId = $_POST['item_id'];
				$collectionId = $_POST['collection_id'];
				$collectionName = $_POST['collection_name'];
				$list_data = $this->Itemlists->find()->where(['id' => $collectionId])->andWhere(['user_id' => $userId])->first();
				$check_collection_name = $this->Itemlists->find()->where(['lists' => $collectionName])->count();
				if ($check_collection_name == '0') {
					if (count($list_data) == 0) {
						$categoryModel = $this->Categories->find()->where(['category_name LIKE' => $collectionName])->count();
						$cattype = 1;
						if (count($categoryModel) != 0) {
							$cattype = 0;
						}
						$collection_data = $this->Itemlists->newEntity();
						$jsonItemList[] = $itemId;
						$jsonItemList = json_encode($jsonItemList);
						$collection_data->user_id = $userId;
						$collection_data->lists = $collectionName;
						$collection_data->list_item_id = $jsonItemList;
						$collection_data->user_create_list = $cattype;
						$collection_data->created_on = date('Y-m-d H:i:s', time());
						$result = $this->Itemlists->save($collection_data);
						$collection_id = $result->id;
					} else {
						$this->Itemlists->updateAll(array('lists' => $collectionName), array('id' => $collectionId));
						$collection_id = $list_data['id'];

					}
					$list_datas = $this->Itemlists->find()->where(['id' => $collection_id])->first();
					$resultarray['collection_id'] = $list_datas['id'];
					$resultarray['collection_name'] = $list_datas['lists'];
					echo '{"status": "true","result": ' . json_encode($resultarray) . '}';
					die;
				} else {
					echo '{"status": "false","result": "collection already exist"}';
					die;
				}
			}

			function deleteCollection()
			{

				$this->loadModel('Itemlists');
				$userId = $_POST['user_id'];

				$collectionId = $_POST['collection_id'];
				if ($this->Itemlists->deleteAll(array('id' => $collectionId), false)) {
					echo '{"status":"true","message":"Deleted successfully"}';
					die;
				} else {
					echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
					die;
				}
			}

			function viewCollection()
			{

				$user_id = $_POST['user_id'];
				$listId = $_POST['collection_id'];

				$this->loadModel('Itemlists');
				$this->loadModel('Items');
				$this->loadModel('Itemfavs');
				$this->loadModel('Followers');

				$offset = 0;
				$limit = 10;
				if (isset($_POST['offset'])) {
					$offset = $_POST['offset'];
				}
				if (isset($_POST['limit'])) {
					$limit = $_POST['limit'];
				}

		$itemListModel = $this->Itemlists->find()->where(['id' => $listId])->first();//ById($listId);

		if (count($itemListModel) != 0) {
			$itemids = json_decode($itemListModel['list_item_id'], true);
			foreach ($itemids as $itemidss) {
				if ($itemidss != "")
					$ids[] = $itemidss;

			}

			$itemModel = TableRegistry::get('Items')->find()->contain('Forexrates')->where(['Items.id IN' => $itemids])->limit($limit)->offset($offset)->all();

			$items_fav_data = $this->Itemfavs->find()->where(['user_id' => $user_id])->all();//all',array('conditions'=>array('user_id'=>$userId)));
			if (count($items_fav_data) > 0) {
				foreach ($items_fav_data as $favitems) {
						//echo "<pre>";print_r($favitems['Itemfav']['item_id']);die;
					$favitems_ids[] = $favitems['item_id'];
				}
			} else {
				$favitems_ids = array();
			}
			$resultArray = $this->convertJsonHome($itemModel, $favitems_ids, $user_id, 1);

			$resultarray1['collection_id'] = $itemListModel['id'];
			$resultarray1['collection_name'] = $itemListModel['lists'];
			$resultarray1['type'] = $itemListModel['user_create_list'];
			$resultarray1['total_items'] = count($ids);
			$resultarray1['items'] = $resultArray;
			echo '{"status":"true","result":' . json_encode($resultarray1) . '}';
			die;
				//echo "$offset $limit <pre>";print_R($itemids);print_R($itemModel);
		}
		echo '{"status":"true","message":"No Data Found"}';
		die;
	}
	function myCollection()
	{

		$user_id = $_POST['user_id'];

		$this->loadModel('Itemlists');
		$this->loadModel('Items');
		$this->loadModel('Itemfavs');
		$this->loadModel('Forexrates');

		$offset = 0;
		$limit = 5;
		if (isset($_POST['offset'])) {
			$offset = $_POST['offset'];
		}
		if (isset($_POST['limit'])) {
			$limit = $_POST['limit'];
		}

		if (isset($_POST['offset'])) {
			$itemListModel = $this->Itemlists->find('all', array(
				'conditions' => array(
					'user_id' => $user_id
				),
				'limit' => $limit,
				'offset' => $_POST['offset'],
				'order' => 'id DESC',
			));
		} else {
			$itemListModel = $this->Itemlists->find('all', array(
				'conditions' => array(
					'user_id' => $user_id
				),
				'limit' => $limit,
				'order' => 'id DESC',
			));

		}

			//$itemListModel = $this->Itemlists->find()->where(['user_id'=>$user_id])->all();

		//if (count($itemListModel)!=0)
		//{
		foreach ($itemListModel as $key => $itemListModels) {

			$itemids = json_decode($itemListModels['list_item_id'], true);

			if (!empty($itemids)) {
				if (isset($_POST['offset'])) {
					$itemModel = $this->Items->find('all', array(
						'conditions' => array(
							'Items.id IN' => $itemids
						),
						'limit' => $limit,
						'offset' => $_POST['offset'],

					))->contain('Forexrates');
				} else {
					$itemModel = $this->Items->find('all', array(
						'conditions' => array(
							'Items.id IN' => $itemids
						),
						'limit' => $limit,

					))->contain('Forexrates');

				}

					//$itemModel = $this->Items->find()->where(['id IN'=>$itemids])->all();

				$items_fav_data = $this->Itemfavs->find()->where(['user_id' => $user_id])->all();//all',array('conditions'=>array('user_id'=>$userId)));
				if (count($items_fav_data) > 0) {
					foreach ($items_fav_data as $favitems) {
							//echo "<pre>";print_r($favitems['Itemfav']['item_id']);die;
						$favitems_ids[] = $favitems['item_id'];
					}
				} else {
					$favitems_ids = array();
				}
				$resultArray = $this->convertJsonHome($itemModel, $favitems_ids, $user_id, 1);
			} else {
				$resultArray = array();
			}

			$resultarray1[$key]['collection_id'] = $itemListModels['id'];
			$resultarray1[$key]['collection_name'] = $itemListModels['lists'];
			$resultarray1[$key]['type'] = $itemListModels['user_create_list'];

			$resultarray1[$key]['total_items'] = count($resultArray);
			$resultarray1[$key]['items'] = $resultArray;

				//echo "$offset $limit <pre>";print_R($itemids);print_R($itemModel);
		}
		if (empty($resultarray1)) {
			echo '{"status":"false","message":"No data found"}';
			die;
		}
		echo '{"status":"true","result":' . json_encode($resultarray1) . '}';
		die;

		//}
		//else
		//{
		//	echo '{"status":"false","message":"No Data Found"}';die;
		//}
	}

	function allStores()
	{

		$user_id = $_POST['user_id'];

		$search_key = $_POST['search_key'];
		$this->loadModel('Shops');
		$this->loadModel('Storefollowers');

		$setngs = $this->Sitesettings->find()->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}

		$offset = 0;
		$limit = 10;
		if (isset($_POST['offset'])) {
			$offset = $_POST['offset'];
		}
		if (isset($_POST['limit'])) {
			$limit = $_POST['limit'];
		}
		$condition = array('seller_status' => 1, 'shop_name LIKE' => '%' . $search_key . '%');
		$condition1 = array('seller_status' => 1);
		if (!empty($search_key) || $search_key = "") {
			if (!empty($_POST['offset'])) {
				$shopsdet = $this->Shops->find('all', array(
					'conditions' => $condition,
					'limit' => $limit,
					'offset' => $_POST['offset'],

				));
			} else {

				$shopsdet = $this->Shops->find('all', array(
					'conditions' => $condition,
					'limit' => $limit,
				));
			}
		} else {
			if (!empty($_POST['offset'])) {
				$shopsdet = $this->Shops->find('all', array(
					'conditions' => $condition1,
					'limit' => $limit,
					'offset' => $_POST['offset'],

				));
			} else {

				$shopsdet = $this->Shops->find('all', array(
					'conditions' => $condition1,
					'limit' => $limit,
				));
			}
		}

		foreach ($shopsdet as $key => $shops) {

			$profileimage = $shops['shop_image']; //echo $profileimage;die;
			if (empty($profileimage)) {
				$profileimage = "usrimg.jpg";
			}
			$storeid = $shops['id'];

			$followers = $this->Storefollowers->find()->where(['store_id' => $storeid])->all();//all',array('conditions'=>array('Storefollower.store_id'=>$storeid)));
			$flwrusrids = array();
			foreach ($followers as $follower) {
				$flwrusrids[] = $follower['follow_user_id'];
			}
			$resultarray[$key]['store_id'] = $shops['id'];

			$resultarray[$key]['store_name'] = $shops['shop_name'];
			$resultarray[$key]['wifi'] = $shops['Shop']['wifi'];
			$resultarray[$key]['merchant_name'] = $shops['merchant_name'];
			if (in_array($user_id, $flwrusrids)) {
				$resultarray[$key]['status'] = 'unfollow';
			} else {
				$resultarray[$key]['status'] = 'follow';
			}
			$resultarray[$key]['image'] = $img_path . 'media/avatars/thumb150/' . $profileimage;
		}

		if (!empty($resultarray)) {
			$resultarray = json_encode($resultarray);
			echo '{"status":"true","result":' . $resultarray . '}';
			die;
		} else
		echo '{"status":"false","message":"No stores found"}';
		die;

	}

	function itemDetails()
	{
		$item_id = $_POST['item_id'];
		$user_id = $_POST['user_id'];
		$this->loadModel('Contactsellers');
		$this->loadModel('Itemreviews');
		$this->loadModel('Sitequeries');
		$this->loadModel('Facebookcoupons');
		$this->loadModel('Forexrates');
		$this->loadModel('Sellercoupons');
		$this->loadModel('Photos');
		$this->loadModel('Users');
		$this->loadModel('Searchitems');
		$this->loadModel('Followers');
		$this->loadModel('Items');
		$this->loadModel('Itemfavs');
		$this->loadModel('Storefollowers');
		$this->loadModel('Shops');
		$this->loadModel('Fashionusers');
		$this->loadModel('Sitesettings');
		$this->loadModel('Comments');
		$setngs = $this->Sitesettings->find()->toArray();

		$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();

		//if(isset($_POST['get_type']) && $_POST['get_type'] == 'search' && !empty($user_id))
		

		$resultArray = array();

		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}
		$userDetail = $this->Users->find()->where(['id' => $user_id])->first();

		if (!empty($userDetail))
			$userId = $userDetail['id'];
		else
			$userId = $userId;

		$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();
		if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "") {

			$cur_symbol = $forexrateModel['currency_symbol'];

			$cur = $forexrateModel['price'];
		} else {

			$cur_symbol = $currency_value['currency_symbol'];
			$cur = $currency_value['price'];
		}

		$listitem = TableRegistry::get('Items')->find()->contain('Forexrates')->where(['Items.id' => $item_id])->andWhere(['Items.status' => 'publish'])->first();

		if (!isset($listitem) && empty($listitem)) {
			echo '{"status": "false", "message": "No data found"}';
			die;
		}


		if(!empty($user_id))
		{
			//echo $listitem['category_id']; die;
			 $this->Searchitems->deleteAll(array('sourceid' => $item_id, 'userid' => $user_id), false);

			$item_categoryid = $listitem['category_id'];
	        $searchitemstable = TableRegistry::get('Searchitems');
	        $searchitems = $searchitemstable->newEntity();
	        $searchitems->sourceid = $item_id;
	        $searchitems->category_id = $item_categoryid;
	        $searchitems->userid = $user_id;
	        $searchitems->type = 'item';
	        $result = $this->Searchitems->save($searchitems);
	        $itemId = $result->id;
		}
		
		$price = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $listitem['price']);

		//echo count($listitem); die;

		

		$reportUsers = '';
		$process_time = $listitem['processing_time'];
		if ($process_time == '1d') {
			$process_time = "One business day";
		} elseif ($process_time == '2d') {
			$process_time = "Two business days";
		} elseif ($process_time == '3d') {
			$process_time = "Three business days";
		} elseif ($process_time == '4d') {
			$process_time = "Four business days";
		} elseif ($process_time == '2ww') {
			$process_time = "One-Two weeks";
		} elseif ($process_time == '3w') {
			$process_time = "Two-Three weeks";
		} elseif ($process_time == '4w') {
			$process_time = "Three-Four weeks";
		} elseif ($process_time == '6w') {
			$process_time = "Four-Six weeks";
		} elseif ($process_time == '8w') {
			$process_time = "Six-Eight weeks";
		}
		$shareSeller = $listitem['share_coupon'];

		$shareCouponDetail = $this->Facebookcoupons->find()->where(['item_id' => $listitem['id']])->andWhere(['user_id' => $user_id])->all();
		if (count($shareCouponDetail) != 0)
			$shareUser = "yes";
		else
			$shareUser = "no";

		$convertPrice = round($listitem['price'] * $forexrateModel['price'], 2);
		if ($listitem['featured'] == 1)
			$convertdefaultprice = round(($listitem['price'] * 2) * $listitem['price'], 2);
		else
			$convertdefaultprice = round($listitem['price'] * $listitem['price'], 2);

		$resultArray['id'] = $listitem['id'];
		$resultArray['item_title'] = $listitem['item_title'];
		$itemid = base64_encode($listitem['id'] . "_" . rand(1, 9999));
		$resultArray['product_url'] = SITE_URL . 'listing/' . $itemid;
		$itemshareid = base64_encode($listitem['id'] . "_" . rand(1, 9999) . "_". $user_id);
		$resultArray['product_share_url'] = SITE_URL . 'listing/' . $itemshareid;
		$resultArray['item_description'] = $listitem['item_description'];
		$resultArray['shipping_time'] = $process_time;
		$resultArray['currency'] = $cur_symbol;
		$resultArray['mainprice'] = $listitem['price'];
		if($listitem['affiliate_commission'] > 0) 
		$resultArray['commision_percentage'] = $listitem['affiliate_commission'];

		$tdy = strtotime(date("Y-m-d"));

		if (strtotime($listitem['dealdate']) == $tdy && $listitem['discount_type'] == 'daily') {
			$resultArray['deal_enabled'] = 'yes';
			$resultArray['pro_discount'] = 'dailydeal';
			$resultArray['discount_percentage'] = $listitem['discount'];
		} elseif($listitem['discount_type'] == 'regular') {
			$resultArray['deal_enabled'] = 'yes';
			$resultArray['pro_discount'] = 'regulardeal';
			$resultArray['discount_percentage'] = $listitem['discount'];
		}else{
			$resultArray['deal_enabled'] = 'no';
			$resultArray['discount_percentage'] = 0;
		}

		$resultArray['fbshare_discount'] = $listitem['share_discountAmount'];
		$resultArray['valid_till'] = (!isset($listitem['dealdate'])) ? '' : strtotime($listitem['dealdate']) ;
		$resultArray['quantity'] = $listitem['quantity'];
		$resultArray['cod'] = $listitem['cod'];

		$itemDiscount = $this->Sellercoupons->find()->where([
			'sourceid' => $item_id,
			'type'=>'item',
			'remainrange !='=>'0'
			])->order(['id'=>'desc'])->first();


		$categoryDiscount = $this->Sellercoupons->find()->where([
			'sourceid' => $listitem['category_id'],
			'sellerid'=>$listitem['user_id'],
			'type'=>'category',
			'remainrange !='=>'0'
			])->order(['id'=>'desc'])->first();



		

		
		$now = strtotime(date('m/d/Y'));
		
		if($now <= strtotime($itemDiscount->validto))
		{
			$itemDiscount = $itemDiscount;
		}else{
			$itemDiscount = '';
		}


		if($now <= strtotime($categoryDiscount->validto))
		{
			$categoryDiscount = $categoryDiscount;
		}else{
			$categoryDiscount = '';
		}

		if((!empty($itemDiscount))){
			$resultArray['seller_offer']['couponcode'] = $itemDiscount->couponcode;
			$resultArray['seller_offer']['couponpercentage'] = $itemDiscount->couponpercentage;
			$resultArray['seller_offer']['validfrom'] = date("M d", strtotime($itemDiscount->validfrom));
			$resultArray['seller_offer']['validto'] = date("M d", strtotime($itemDiscount->validto));	
			$resultArray['seller_offer']['coupon_count'] = $itemDiscount->totalrange;
		}else{
			$resultArray['seller_offer'] = (object) array();
		}
		
		//xxx use this coupon to get extra 10% discount from April 10 to April 15. Limited for first 10 purchases only.
		if((!empty($categoryDiscount))){
			$resultArray['category_offer']['couponcode'] = $categoryDiscount->couponcode;
			$resultArray['category_offer']['couponpercentage'] = $categoryDiscount->couponpercentage;
			$resultArray['category_offer']['validfrom'] = date("M d", strtotime($categoryDiscount->validfrom));
			$resultArray['category_offer']['validto'] = date("M d", strtotime($categoryDiscount->validto));
			$resultArray['category_offer']['coupon_count'] = $categoryDiscount->totalrange;
		}else{
			$resultArray['category_offer'] = (object) array();
		}

		/*
		if((!empty($cartDiscount))){
			$resultArray['cart_offer']['couponcode'] = $cartDiscount->couponcode;
			$resultArray['cart_offer']['couponpercentage'] = $cartDiscount->couponpercentage;
			$resultArray['cart_offer']['validfrom'] = date("M d", strtotime($cartDiscount->validfrom));
			$resultArray['cart_offer']['validto'] = date("M d", strtotime($cartDiscount->validto));
			$resultArray['cart_offer']['coupon_count'] = $cartDiscount->totalrange;
		}else{
			$resultArray['cart_offer'] = (object) array();
		}
		*/

		//$resultArray['category_offer'] = (!empty($categoryDiscount)) ? $categoryDiscount->couponcode.' use this coupon to get extra '.$categoryDiscount->couponpercentage.'% discount from '.date("M d", strtotime($categoryDiscount->validfrom)).' to '.date("M d", strtotime($categoryDiscount->validto)).' limited for first '.$categoryDiscount->totalrange : '';

		//$resultArray['admin_offer'] = (object) array();

		$resultArray['size'] = [];

		if (empty($listitem['size_options'])) {
			$resultArray['size'] = [];
		} else {
			$sizes = json_decode($listitem['size_options'], true);
			$sqkey = 0;
			$setPrice = 0;
			foreach ($sizes['size'] as $val) {
				if (count($sizes['unit'][$val]) > 0) {
					$resultArray['size'][$sqkey]['name'] = $val;
					$resultArray['size'][$sqkey]['qty'] = $sizes['unit'][$val];
					$resultArray['size'][$sqkey]['price'] = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $sizes['price'][$val]);
					if(($sizes['unit'][$val] > 0) && ($setPrice==0))
					{
						$price = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $sizes['price'][$val]);
						$setPrice++;
					}
					$sqkey++;
				}
			}
		}

		if ($currency_value['currency_code'] != $forexrateModel['currency_code'])
			$resultArray['price'] = $price;
		else
			$resultArray['price'] = $price;

		$shop_data = $this->Shops->find()->where(['id' => $listitem['shop_id']])->first();
		$shop_image = $shop_data['shop_image'];

		if ($shop_image == "")
			$shop_image = "usrimg.jpg";

		$resultArray['shop_id'] = $shop_data['id'];
		$resultArray['shop_name'] = $shop_data['shop_name_url'];
		$resultArray['shop_image'] = $img_path . 'media/avatars/thumb350/' . $shop_image;
		$resultArray['shop_address'] = $shop_data['shop_address'];

		$store_follow_status = $this->Storefollowers->find()->where(['store_id' => $shop_data['id']])->andwhere(['follow_user_id' => $user_id])->first();
		if (count($store_follow_status) == 0) {
			$resultArray['store_follow'] = "no";
		} else {
			$resultArray['store_follow'] = "yes";
		}

		if ($listitem['featured'] == 1)
			$convertdefaultprice = round(($listitem['price'] * 2) * $listitem['price'], 2);
		else
			$convertdefaultprice = round($listitem['price'] * $listitem['price'], 2);

		$resultArray['latitude'] = $shop_data['shop_latitude'];
		$resultArray['longitude'] = $shop_data['shop_longitude'];
		$likedcount = $this->Itemfavs->find()->where(['item_id' => $listitem['id']])->count();
		$resultArray['like_count'] = $likedcount;
		$resultArray['reward_points'] = floor($convertdefaultprice);
		$resultArray['share_seller'] = $shareSeller;
		$resultArray['share_user'] = $shareUser;

		if ($listitem['status'] == 'things') {
			$resultArray['buy_type'] = "affiliate";
		} else if ($listitem['status'] == 'publish') {
			$resultArray['buy_type'] = "buy";
		}
		$resultArray['affiliate_link'] = $listitem['bm_redircturl'];
		if ($listitem['status'] == 'publish') {
			$resultArray['approve'] = true;
		} else {
			$resultArray['approve'] = false;
		}

		$item_status = json_decode($listitem['report_flag'], true);

		if (in_array($user_id, $item_status)) {
			$report_status = "yes";
		} else {
			$report_status = "no";

		}
		$resultArray['report'] = $report_status;
		$liked_status = $this->Itemfavs->find()->where(['item_id' => $item_id])->andwhere(['user_id' => $user_id])->first();
		if (count($liked_status) == 0) {
			$resultArray['liked'] = "no";
		} else {
			$resultArray['liked'] = "yes";
		}
		$resultArray['video_url'] = $listitem['videourrl'];

		$photos = $this->Photos->find()->where(['item_id' => $item_id])->all();
		$itemCount = 0;
		$resultArray['photos'] = array();
		$itemCount = 0;
		foreach ($photos as $keys => $photo) {
			if ($listitem['id'] == $photo['item_id']) {
				if ($keys == 0) {
					$resultArray['photos'][$itemCount]['item_url_main_70'] = $img_path . 'media/items/thumb70/' . $photo['image_name'];

				} else {
					$resultArray['photos'][$itemCount]['item_url_main_70'] = $img_path . 'media/items/thumb70/' . $photo['image_name'];

				}

				if ($keys == 0) {
					$image = $img_path . 'media/items/thumb350/' . $photo['image_name'];
					list($width, $height) = getimagesize($image);
					$resultArray['photos'][$itemCount]['item_url_main_350'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];
					$resultArray['photos'][$itemCount]['height'] = $height;
					$resultArray['photos'][$itemCount]['width'] = $width;

				} else {
					$image = $img_path . 'media/items/thumb350/' . $photo['image_name'];
					list($width, $height) = getimagesize($image);
					$resultArray['photos'][$itemCount]['item_url_main_350'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];

					$resultArray['photos'][$itemCount]['height'] = $height;
					$resultArray['photos'][$itemCount]['width'] = $width;

				}

				if ($keys == 0) {
					$resultArray['photos'][$itemCount]['item_url_main_original'] = $img_path . 'media/items/original/' . $photo['image_name'];

				} else {
					$resultArray['photos'][$itemCount]['item_url_main_original'] = $img_path . 'media/items/original/' . $photo['image_name'];

				}

				$itemCount += 1;
			}
		}
		$fashion_data = $this->Fashionusers->find()->where(['itemId' => $item_id])->andWhere(['status' => "YES"])->order(['id' => 'DESC'])->all();
		$resultArray['product_selfies'] = array();
		foreach ($fashion_data as $key => $fashion_datas) {
			$resultArray['product_selfies'][$key]['image_350'] = $img_path . 'media/avatars/thumb350/' . $fashion_datas['userimage'];
			$resultArray['product_selfies'][$key]['image_original'] = $img_path . 'media/avatars/original/' . $fashion_datas['userimage'];
			$resultArray['product_selfies'][$key]['user_id'] = $fashion_datas['user_id'];

			$user_detail1 = $this->Users->find()->where(['id' => $fashion_datas['user_id']])->first();
			$profileimage = $user_detail1['profile_image'];
			if ($profileimage == "") {
				$profileimage = "usrimg.jpg";
			}

			$resultArray['product_selfies'][$key]['user_name'] = $user_detail1['username'];
			$resultArray['product_selfies'][$key]['user_image'] = ($profileimage != '') ? $img_path."media/avatars/thumb70/".$profileimage : $img_path."media/avatars/thumb70/usrimg.jpg";

			//$img_path . 'media/avatars/thumb150/' . $profileimage;
		}

		$Details = $this->Comments->find()->where(['item_id' => $item_id])->order(['id' => 'DESC'])->limit(2);
		$resultArray['recent_comments'] = array();
		foreach ($Details as $key => $details) {
			$resultArray['recent_comments'][$key]['comment_id'] = $details['id'];
			$resultArray['recent_comments'][$key]['comment'] = $details['comments'];
			$resultArray['recent_comments'][$key]['user_id'] = $details['user_id'];
			$user_detail = $this->Users->find()->where(['id' => $details['user_id']])->first();
			$profileimage = $user_detail['profile_image'];
			if ($profileimage == "") {
				$profileimage = "usrimg.jpg";
			}
			$resultArray['recent_comments'][$key]['user_image'] = $img_path . 'media/avatars/thumb150/' . $profileimage;
			$resultArray['recent_comments'][$key]['user_name'] = $user_detail['username'];
			$resultArray['recent_comments'][$key]['full_name'] = $user_detail['first_name'] . ' ' . $user_detail['last_name'];
		}
		$items_data = $this->Items->find('all', array(
			'conditions' => array(
				'Items.shop_id' => $shop_data['id'],
				'Items.id <>' => $item_id,
				'Items.status' => 'publish',
				'Items.affiliate_commission IS NULL',
			),
			'limit' => 10,

		))->contain('Forexrates');
		$items_data1 = $this->Items->find('all', array(
			'conditions' => array(
				'Items.category_id' => $listitem['category_id'],
				'Items.id <>' => $item_id,
				'Items.status' => 'publish',
				'Items.affiliate_commission IS NULL',
			),
			'limit' => 10,

		))->contain('Forexrates');

		$items_fav_data = $this->Itemfavs->find()->where(['user_id' => $user_id])->all();
		if (count($items_fav_data) > 0) {
			foreach ($items_fav_data as $favitems) {
				$favitems_ids[] = $favitems['item_id'];
			}
		} else {
			$favitems_ids = array();
		}

		$sellerData = $this->Users->find()->where(['id' => $listitem['user_id']])->first();
		$sellerAvgRate = $this->getsellerAverage($listitem['user_id']);
		$resultArray['average_rating'] = $sellerAvgRate['rating'];

		$resultArray['store_products'] = $this->convertJsonHome($items_data, $favitems_ids, $user_id, 1);
		$resultArray['similar_products'] = $this->convertJsonHome($items_data1, $favitems_ids, $user_id, 1);

		$inputArray = array('item_id'=>$item_id);
		$resultArray['recent_questions'] = $this->getlatestproduct_faq($inputArray);

		//$resultArray['item_reviews'] = $this->getitemreviews($item_id);

		$itemreviewTable = TableRegistry::get('Itemreviews');
		$reviewData = $this->Itemreviews->find('all', array(
				'conditions' => array(
					'itemid' => $item_id
				),
				'limit' => 2,
				'offset' => 0,
				'order' => 'id DESC',
			))->all();


		$reviewCount = $this->Itemreviews->find('all', array(
				'conditions' => array(
					'itemid' => $item_id,
				),
				'order' => 'id DESC',
			))->count();

		$datanewSourceObject = ConnectionManager::get('default');
    	$reviews_s = $datanewSourceObject->execute("SELECT * from fc_itemreviews where itemid=".$item_id." AND reviews!=''")->fetchAll('assoc');

    	//print_r($reviews_s); die;


		$getAvgrat = $this->getAverage($item_id);
		$result = array();


		//$userImage = $img_path . "media/avatars/thumb70/" . $userImage;
		
		foreach($reviewData as $key=>$eachreview)
		{
			$user_data = $this->Users->find()->where(['id' => $eachreview['userid']])->first();
			$result[$key]['user_id'] = $eachreview['userid'];
			$result[$key]['user_name'] = $user_data['username'];
			$result[$key]['user_image'] = ($user_data['profile_image'] != '') ? $img_path . "media/avatars/thumb70/".$user_data['profile_image'] : $img_path . "media/avatars/thumb70/usrimg.jpg";
			$result[$key]['id'] = $eachreview['orderid'];
			$result[$key]['review_title'] = $eachreview['review_title'];
			$result[$key]['rating'] = $eachreview['ratings'];
			$result[$key]['review'] = $eachreview['reviews'];
		}

		$datanewSourceObject = ConnectionManager::get('default');
    	$ratingstmt = $datanewSourceObject->execute("SELECT count(*) as Total, round(ratings) as ratings from fc_itemreviews where itemid=".$item_id." group by ratings order by ratings desc
		")->fetchAll('assoc');

		//echo '<pre>'; print_r($ratingstmt); die;

    	$byrateGroup = $this->group_by("ratings", $ratingstmt);

    	//echo '<pre>'; print_r($byrateGroup); die;
		$rating_count = ($byrateGroup[5][0]['Total']+$byrateGroup[4][0]['Total']+$byrateGroup[3][0]['Total']+$byrateGroup[2][0]['Total']+$byrateGroup[1][0]['Total']);
		
		$five = (empty($byrateGroup[5][0]['Total'])) ? 0 : $byrateGroup[5][0]['Total'] ;
		$four = (empty($byrateGroup[4][0]['Total'])) ? 0 : $byrateGroup[4][0]['Total'] ;
		$three = (empty($byrateGroup[3][0]['Total'])) ? 0 : $byrateGroup[3][0]['Total'] ;
		$two = (empty($byrateGroup[2][0]['Total'])) ? 0 : $byrateGroup[2][0]['Total'] ;
		$one = (empty($byrateGroup[1][0]['Total'])) ? 0 : $byrateGroup[1][0]['Total'] ;

		$avg_rating_ns = ($listitem['avg_rating'] == 0) ? '0' : $listitem['avg_rating'];
		$resultArray['item_reviews'] = array(
			'review_count'=>count($reviews_s),
			'rating'=>$avg_rating_ns,
			'rating_count'=>$rating_count,
			'five'=>$five,
			'four'=>$four,
			'three'=>$three,
			'two'=>$two,
			'one'=>$one,
			'result'=>$result
			);
		
		$orderstable = TableRegistry::get('Orders');
		$orderitemstable = TableRegistry::get('OrderItems');

		$ordersModel = $orderstable->find('all')->where(['userid' => $user_id])->order(['orderid DESC'])->all();
		$orderid = array();
        foreach ($ordersModel as $value) {
        	if($value['status'] == 'Delivered' || $value['status'] == 'Paid')
        	{
        		$orderid[] = $value['orderid'];
        	}
        }

        if(!empty($orderid))
        {
        	$orderitemModel = $orderitemstable->find('all')->where(['itemid'=>$item_id,'orderid IN' => $orderid])->order(['orderid DESC'])->first();	
        	$resultArray['order_id'] = (isset($orderitemModel->orderid)) ? $orderitemModel->orderid : '';
        	if(isset($orderitemModel->orderid)){
        		$get_review = TableRegistry::get('Itemreviews');
				$firstreviewData = $this->Itemreviews->find('all', array(
						'conditions' => array(
							'itemid' => $item_id,
							'orderid'=> $orderitemModel->orderid
						),
						'order' => 'id DESC',
					))->first();
				$resultArray['review_id'] = (isset($firstreviewData->id)) ? $firstreviewData->id : '';
        	}
        }else{
        	$resultArray['order_id'] = '';
        	$resultArray['review_id'] = '';
        }
		

		//echo '<pre>'; print_r($orderitemModel); die;

		if (count($resultArray) != 0) {
			$resultArray = json_encode($resultArray);
			echo '{"status":"true","result":' . $resultArray . '}';
			die;
		} else
		echo '{"status":"false","message":"No Item found"}';
		die;
	}

	public function myOrders()
	{
		if ($this->sitemaintenance() == 0) {
			echo '{"status":"error","message":"Site under maintenance mode"}';
			die;
		}

		$userid = $_POST['user_id'];

		$this->loadModel('Photos');
		$this->loadModel('Orders');
		$this->loadModel('Order_items');
		$this->loadModel('Items');
		$this->loadModel('Users');
		$this->loadModel('Forexrates');
		$this->loadModel('Sitesettings');
		$setngs = $this->Sitesettings->find()->toArray();

		$resultArray = array();

		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}
		$offset = 0;
		$limit = 10;
		if (isset($_POST['offset'])) {
			$offset = $_POST['offset'];
		}
		if (isset($_POST['limit'])) {
			$limit = $_POST['limit'];
		}
		if (!empty($_POST['offset'])) {
			$order_data = $this->Orders->find('all', array(
				'conditions' => array(
					'userid' => $userid,
				),
				'limit' => $limit,
				'offset' => $_POST['offset'],
				'order' => 'orderid DESC',

			));

		} else {

			$order_data = $this->Orders->find('all', array(
				'conditions' => array(
					'userid' => $userid,
				),

				'limit' => $limit,
				'order' => 'orderid DESC',
			));
		}

				//$order_data = $this->Orders->find()->where(['userid'=>$userid])->all();
		if (count($order_data) == 0) {
			echo '{"status": "false", "message": "No data found"}';
			die;

		}
		foreach ($order_data as $key => $order_datas) {

			$resultArray[$key]['order_id'] = $order_datas['orderid'];
			$resultArray[$key]['grand_total'] = $order_datas['totalcost'] + $order_datas['tax'];
			$forexrate = $this->Forexrates->find()->where(['currency_code' => $order_datas['currency']])->first();
			$resultArray[$key]['currency'] = $forexrate['currency_symbol'];
			$resultArray[$key]['sale_date'] = $order_datas['orderdate'];

			$item_data = $this->Order_items->find()->where(['orderid' => $order_datas['orderid']])->first();

			$thisitemiddetails = $this->Items->find()->where(['id' => $item_data['itemid']])->first();

			$businessday = $thisitemiddetails['processing_time'];

			$business = str_split($businessday);

			$saledate = date('Y-m-d', $order_datas['orderdate']);
			$deliverydate = date("Y-m-d", strtotime($saledate . ' + 2 day'));
			if ($business[1] == 'd') {
				$expected_delivery = strtotime($deliverydate . ' + ' . $business[0] . ' day');
				$expected_from = date("Y-m-d", strtotime($deliverydate . ' + ' . $business[0] . ' day'));
			} else {
				$original_business = $business[0] * 6;
				$expected_delivery = strtotime($deliverydate . ' + ' . $original_business . ' day');
				$expected_from = date("Y-m-d", strtotime($deliverydate . ' + ' . $original_business . ' day'));
			}
			$expected_to = strtotime($expected_from . ' + 3 day');
			$xpdate['from'] = $expected_delivery;
			$xpdate['to'] = $expected_to;

			$resultArray[$key]['expected_delivery'] = $xpdate;

			$resultArray[$key]['status'] = $order_datas['status'];

			$resultArray[$key]['item_id'] = $item_data['itemid'];
			$resultArray[$key]['item_name'] = $item_data['itemname'];
			$photo = $this->Photos->find()->where(['item_id' => $item_data['itemid']])->first();
			$resultArray[$key]['item_image'] = $img_path . 'media/items/thumb150/' . $photo['image_name'];
		}

		if (empty($resultArray)) {
			echo '{"status": "false", "message": "No data found"}';
			die;
		}
		echo ' {"status": "true", "result": ' . json_encode($resultArray) . '}';
		die;

	}

	function writeReview()
	{

		$this->loadModel('Reviews');
		$this->loadModel('Users');
		$this->loadModel('Shops');
		$this->loadModel('Orders');

		$userid = $_POST['user_id'];

		$storeid = $_POST['store_id'];
		$orderid = $_POST['order_id'];
		$ratings = $_POST['rating'];
		$reviews = $_POST['message'];
		$review_title = $_POST['title'];
		$shop_data = $this->Shops->find()->where(['id' => $storeid])->first();
		$sellerid = $shop_data['user_id'];
		$review_data = $this->Reviews->newEntity();
		$review_data->orderid = $orderid;

		$review_data->userid = $userid;
		$review_data->sellerid = $sellerid;
		$review_data->review_title = $review_title;
		$review_data->reviews = $reviews;
		$review_data->ratings = $ratings;
		$review_data->date = date('Y-m-d H:i:s');

		$order_datas = $this->Orders->find()->where(['userid' => $userid, ['merchant_id' => $sellerid]])->andWhere(['reviews <>' => 1])->all();//Orders.reviews !=' => '1')));

		foreach ($order_datas as $orders) {
			$orderids[] = $orders['orderid'];
		}

		$order_date = $this->Orders->find('all', array(
			'conditions' => array(
				'userid' => $userid,
				'merchant_id' => $sellerid,
			),

			'limit' => 1,
			'order' => 'orderdate DESC',
		))->toArray();

			//$order_date = $this->Orders->find('all',array('conditions'=>array('userid'=>$userid,'merchant_id'=>$sellerid),'fields'=>array(max('orderdate'))))->toArray();

			//$order_date = $this->Orders->find()->where(['userid'=>$userid,['merchant_id'=>$sellerid]])->andWhere(['fields'=>array('max(orderdate) as maxorderdate')])->all();
		$today = time();
		$review_date = $order_date[0]['orderdate'] + 2505600;
			//echo $today;echo "<br>"; echo $review_date; die;

			/*$orderdate = date('Y-m-d H:i:s',$order_date[0]['orderdate']);
			//echo $order_date[0]['orderdate']+2505600;
			$today = date('Y-m-d H:i:s');

			$date = date_create($orderdate);
			date_add($date, date_interval_create_from_date_string('30 days'));
			$review_date = date_format($date, 'Y-m-d H:i:s');*/

			if (in_array($orderid, $orderids)) {
				if ($today < $review_date) {
				$ordercount = $this->Reviews->find()->where(['userid' => $userid, ['sellerid' => $sellerid]])->andWhere(['orderid' => $orderid])->count();//all',array('conditions'=>array('userid'=>$userid,'sellerid'=>$sellerid,'orderid'=>$orderid))));
				if ($ordercount == 0)
					$this->Reviews->save($review_data);

				$this->Orders->updateAll(array('reviews' => 1), array('userid' => $userid, 'merchant_id' => $sellerid, 'orderid' => $orderid));
				$rateval_data = $this->Reviews->find()->where(['sellerid' => $sellerid])->all();//all',array('conditions'=>array('sellerid'=>$sellerid)));

				$review_count = count($rateval_data);
				$rateval_total = 0;
				foreach ($rateval_data as $ratevaldata) {
					$rateval_total += $ratevaldata['ratings'];
				}

				$average_rate = $rateval_total / $review_count;

				$average_rate = floor($average_rate * 2) / 2;
				$this->Users->updateAll(array('seller_ratings' => $average_rate), array('id' => $sellerid));
				echo '{"status":"true","message":"Review submitted"}';
				die;
			} else {
				echo '{"status":"false","message":"Date Expires"}';
				die;
			}
		} else {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		}

	}
	function orderDetails()
	{
		$orderid = $_POST['order_id'];
		$this->loadModel('Photos');
		$this->loadModel('Orders');
		$this->loadModel('Order_items');
		$this->loadModel('Items');
		$this->loadModel('Users');
		$this->loadModel('Forexrates');
		$this->loadModel('Shippingaddresses');
		$this->loadModel('Sitesettings');
		$this->loadModel('Sellercoupons');
		$this->loadModel('Invoiceorders');
		$this->loadModel('Invoices');
		$this->loadModel('Shops');
		$this->loadModel('Itemreviews');
		$this->loadModel('Disputes');
		$this->loadModel('Trackingdetails');
		$setngs = $this->Sitesettings->find()->toArray();

		$resultArray = array();

		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}
		$offset = 0;
		$limit = 10;
		if (isset($_POST['offset'])) {
			$offset = $_POST['offset'];
		}
		if (isset($_POST['limit'])) {
			$limit = $_POST['limit'];
		}
		$order_datas = $this->Orders->find()->where(['orderid' => $orderid])->first();

		$resultArray['order_id'] = $order_datas['orderid'];
		$resultArray['status'] = $order_datas['status'];

		$couponId = $order_datas['coupon_id'];
		$couponDiscount = $this->Sellercoupons->find()->where(['id' => $couponId])->first();
		$discount_amountTwo = ($couponDiscount->couponpercentage / 100);
		$commiItemTotalPrice = floatval($order_datas['totalcost'] * ($discount_amountTwo));

		$forexrate = $this->Forexrates->find()->where(['currency_code' => $order_datas['currency']])->first();
		$resultArray['currency'] = $forexrate['currency_symbol'];

		$order_itm_datas = $this->Order_items->find()->where(['orderid' => $orderid])->all();

		$item_tot = 0;
		foreach($order_itm_datas as $key=>$eachOrdItm)
		{
			//print_r($eachOrdItm); die;
			$item_tot += $eachOrdItm->itemprice;
		}

		//echo 'item_total'.$item_tot; die;



		//echo $order_datas['totalcost'];
		//echo '<br/>'.$order_datas['totalCostshipp'];
		//echo '<br/>'.$order_datas['tax'];
		//echo '<br/>'.$commiItemTotalPrice;
		//die;

		//$resultArray['grand_total'] = $order_datas['totalcost'] + $order_datas['totalCostshipp'] + $order_datas['tax'] - $commiItemTotalPrice;
		$resultArray['grand_total'] = $order_datas['totalcost'] + $order_datas['totalCostshipp'] + $order_datas['tax'];
		//$resultArray['grand_total'] = $order_datas['totalcost'] + $order_datas['totalCostshipp'] + $order_datas['tax'];
		//$item_total = ($order_datas['totalcost'] == 0) ? $order_datas['discount_amount'] : $order_datas['totalcost'] ;
		//$resultArray['grand_total'] = $order_datas['totalcost'];
		$resultArray['item_total'] = $item_tot;
		$resultArray['shipping_price'] = $order_datas['totalCostshipp'];
		$resultArray['tax'] = $order_datas['tax'];

		
		if ($couponId == 0) {
			$resultArray['coupon_discount'] = 0;
		} else {
			$couponDiscount = $this->Sellercoupons->find()->where(['id' => $couponId])->first();
			$resultArray['coupon_discount'] = $couponDiscount['couponpercentage'] . "%";
		}

		$orderitem_datas = $this->Order_items->find()->where(['orderid' => $orderid])->first();

		//echo '<pre>'; print_r($orderitem_datas); die;

		if ($orderitem_datas['discountType'] == "Credit")
			$resultArray['credit_used'] = $orderitem_datas['discountAmount'];
		else
			$resultArray['credit_used'] = 0;
		if ($orderitem_datas['discountType'] == "Giftcard" || $orderitem_datas['discountType'] == "Giftcard Discount")
			$resultArray['gift_amount'] = $orderitem_datas['discountAmount'];
		else
			$resultArray['gift_amount'] = 0;

		$resultArray['sale_date'] = $order_datas['orderdate'];

		$item_datas = $this->Order_items->find()->where(['orderid' => $order_datas['orderid']])->all();

		$invoiceorders = $this->Invoiceorders->find()->where(['orderid' => $orderid])->first();
		$invoiceid = $invoiceorders['invoiceid'];
		$invoices = $this->Invoices->find()->where(['invoiceid' => $invoiceid])->first();
		$paymentmethod = $order_datas['deliverytype'];
		$resultArray['payment_mode'] = ucfirst($paymentmethod);
		$resultArray['delivery_type'] = $order_datas['deliverytype'];
		$items_data = $this->Items->find()->where(['id' => $item_data['itemid']])->first();

		$shop_data = $this->Shops->find()->where(['user_id' => $order_datas['merchant_id']])->first();
		$resultArray['store_id'] = $shop_data['id'];
		$resultArray['store_name'] = $shop_data['shop_name'];
		$shopImage = $shop_data['shop_image'];
		if ($shopImage != "") {
			$shopImage = $shop_data['shop_image'];

		} else {
			$shopImage = "usrimg.jpg";
		}
		$resultArray['store_image'] = $img_path . 'media/avatars/thumb150/' . $shopImage;
		$resultArray['store_address'] = $shop_data['shop_address'];

		$resultArray['barcode'] = $invoices['invoiceno'];
		$resultArray['barcode_img'] = $img_path . 'barcode/INV_' . $orderid . '.png';


		$review_data = $this->Itemreviews->find()->where(['orderid' => $orderid])->first();
		if (count($review_data) == 0) {
			$reviewId = "";
			$rating = "";
		} else {
			$reviewId = $review_data['id'];
			$rating = $review_data['ratings'];
		}
		$resultArray['review_id'] = $reviewId;
		$resultArray['rating'] = $rating;
		$resultArray['review_title'] = (isset($review_data['review_title'])) ? $review_data['review_title'] : '';
		$resultArray['review_des'] = (isset($review_data['reviews'])) ? $review_data['reviews'] : '';
		$dispute_data = $this->Disputes->find()->where(['uorderid' => $orderid])->first();
		if (count($dispute_data) == 0) {
			$resultArray['dispute_created'] = "no";
			$resultArray['dispute_id'] = 0;
		} else {
			$resultArray['dispute_created'] = "yes";
			$resultArray['dispute_id'] = $dispute_data['disid'];
			$activeStatus = array('Reply', 'Initialized', 'Responded', 'Reopen');
			if (in_array($dispute_data['newstatusup'], $activeStatus))
				$resultArray['dispute_status'] = "Active";
			elseif ($dispute_data['newstatusup'] == 'Accepeted')
				$resultArray['dispute_status'] = "Accepeted";
			else
				$resultArray['dispute_status'] = "Closed";
		}
		$shipping_data = $this->Shippingaddresses->find()->where(['shippingid' => $order_datas['shippingaddress']])->first();
		$resultArray['shipping'] = array();
		$resultArray['shipping']['shipping_id'] = $shipping_data['shippingid'];
		$resultArray['shipping']['full_name'] = $shipping_data['name'];
		$resultArray['shipping']['nick_name'] = $shipping_data['nickname'];
		$resultArray['shipping']['address1'] = $shipping_data['address1'];
		$resultArray['shipping']['address2'] = $shipping_data['address2'];
		$resultArray['shipping']['city'] = $shipping_data['city'];
		$resultArray['shipping']['state'] = $shipping_data['state'];
		$resultArray['shipping']['country'] = $shipping_data['country'];
		$resultArray['shipping']['zipcode'] = $shipping_data['zipcode'];
		$resultArray['shipping']['phone'] = $shipping_data['phone'];
		$resultArray['tracking_details'] = array();
		$tracking_data = $this->Trackingdetails->find()->where(['orderid' => $orderid])->first();
		$resultArray['tracking_details']['id'] = $tracking_data['id'];
		$resultArray['tracking_details']['shipping_date'] = $tracking_data['shippingdate'];
		$resultArray['tracking_details']['courier_name'] = $tracking_data['couriername'];
		$resultArray['tracking_details']['courier_service'] = $tracking_data['courierservice'];
		$resultArray['tracking_details']['tracking_id'] = $tracking_data['trackingid'];
		$resultArray['tracking_details']['notes'] = $tracking_data['notes'];
		$resultArray1 = array();

		$discountAmount = 0;
		$estimated_duration = 'days';
		$estimated_delivery_days = 0;
		$delivered_on = 1;
		foreach ($item_datas as $key => $item_data) {
			$resultArray1[$key]['item_id'] = $item_data['itemid'];
			if ($item_data['discountType'] == 'Coupon Discount' || $item_data['discountType'] == 'Giftcard Discount' || $item_data['discountType'] == 'Giftcard') {
				$discountAmount += $item_data['discountAmount'];
			}
			$photo = $this->Photos->find()->where(['item_id' => $item_data['itemid']])->first();
			if ($photo != 1) {
				$imageName = "usrimg.jpg";
			} else {
				$imageName = WWW_ROOT . 'media/items/original/' . $photo['image_name'];
				if (file_exists($imageName)) {
					$imageName = $photo['image_name'];
				} else {
					$imageName = 'usrimg.jpg';
				}
			}
			$resultArray1[$key]['item_image'] = $img_path . 'media/items/thumb150/' . $imageName;
			$resultArray1[$key]['item_name'] = $item_data['itemname'];
			$resultArray1[$key]['item_skucode'] = $thisitemiddetails['skuid'];
			$resultArray1[$key]['quantity'] = $item_data['itemquantity'];
			$resultArray1[$key]['price'] = $item_data['itemprice'];
			$resultArray1[$key]['size'] = $item_data['item_size'];
			$resultArray1[$key]['deal_percentage'] = $item_data['dealPercentage'];
			$deliverydetails = $this->Items->find()->where(['id' => $item_data['itemid']])->first();

			/* Expected Delivery Date */
			$businessdays = $deliverydetails['processing_time'];
			$business = str_split($businessdays);
			if ($estimated_duration == 'days' && $business[1] == "d") {
				if ($business[0] > $estimated_delivery_days) {
					$estimated_delivery_days = $business[0];
				}
			} else {
				$estimated_duration = 'weeks';
				if ($business[0] > $estimated_delivery_days) {
					$estimated_delivery_days = $business[0];
				}
			}
			$delivered_on = $estimated_delivery_days;
		}

		$ordered_date = date('Y-m-d', $order_datas['orderdate']);
		if ($estimated_duration == 'days') {
			$expected_at = date("Y-m-d", strtotime($ordered_date . ' + ' . $delivered_on . ' day'));
		} else {
			$weekTodays = $delivered_on * 7;
			$expected_at = date("Y-m-d", strtotime($ordered_date . ' + ' . $weekTodays . ' day'));
		}
		//$grndTot = $resultArray['grand_total'] -= $discountAmount;
		//if($grndTot < 0)
		//{
			//echo 'less than 0';
		//	$balanceAmt = 0;
		//}else{
			//echo 'greater than 0';
		//	$balanceAmt = $grndTot;
		//}
		//die;
		//echo $resultArray['grand_total'].'<br/>';
		//echo $discountAmount; die;

		$xpdate['from'] = strtotime($expected_at);
		$xpdate['to'] = strtotime($expected_at);
		$resultArray['expected_delivery'] = $xpdate;
		$resultArray['coupon_discount'] = $discountAmount;
		$resultArray['grand_total'] -= $discountAmount;
		$resultArray['items'] = $resultArray1;
		echo ' {"status": "true", "result": ' . json_encode($resultArray) . '}';
		die;

	}

	function changeOrderStatus()
	{

		$orderid = $_POST['order_id'];
		$status = $_POST['chstatus'];

		$this->loadModel('Orders');
		$this->loadModel('Shippingaddresses');
		$this->loadModel('Order_items');
		$this->loadModel('Sitesettings');
		$this->loadModel('Users');
		$this->loadModel('Invoices');
		$this->loadModel('Invoiceorders');
		$this->loadModel('Items');

		$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}

		$orders = $this->Orders->find()->where(['orderid' => $orderid])->first();
		if (count($orders) == 0) {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		}

		if ($status == 'Delivered') 
		{
			$currentTime = $_POST['current_time'];
			$statusDate = time();
			$this->Orders->updateAll(array('status' => $status, 'status_date' => $statusDate, 'deliver_date' => $currentTime, 'deliver_update' => $statusDate), array('orderid' => $orderid));

			$orderModel = $this->Orders->find()->where(['orderid' => $orderid])->first();
			$logusrid = $orderModel['userid'];
			$userid = $orderModel['merchant_id'];
			$orderitemModel = $this->Order_items->find()->where(['orderid' => $orderid])->all();
			$itemmailids = array();
			$itemname = array();
			$totquantity = array();
			$custmrsizeopt = array();
			foreach ($orderitemModel as $value) {
				$itemmailids[] = $value['itemid'];
				$itemname[] = $value['itemname'];
				if (!empty($value['item_size'])) {
					$custmrsizeopt[] = $value['item_size'];
				} else {
					$custmrsizeopt[] = 0;
				}
				$totquantity[] = $value['itemquantity'];
			}
			$usershipping_addr = $this->Shippingaddresses->find()->where(['shippingid' => $orderModel['shippingaddress']])->first();

			$user_name = $this->Users->find()->where(['id' => $orderModel['merchant_id']])->toArray();
			$username = $user_name[0]['username'];
			$emailaddress = $user_name[0]['email'];
			$buyer_name = $this->Users->find()->where(['id' => $orderModel['userid']])->toArray();
			$buyername = $buyer_name[0]['username'];
			$buyerurl = $buyer_name[0]['username_url'];

			/*** Update the credit amount  while change the status to delivered ***/

			if ($orderModel['deliverytype'] == 'door') {

				$shareData = json_decode($buyer_name[0]['share_status'], true);
				$creditPoints = $buyer_name[0]['credit_points'];
				$userid = $buyer_name[0]['id'];
				$shareNewData = array();
				$user_data = $this->Users->find()->where(['id' => $userid])->first();
				foreach ($shareData as $shareKey => $shareVal) {
					if (array_key_exists($orderid, $shareVal)) {
						if ($shareVal[$orderid] == 1) {
							$user_data->credit_points = $creditPoints + $shareVal['amount'];

							$this->Users->save($user_data);
						} else {

						}

					} else {

						$shareNewData[] = $shareVal;

					}
				}

				$user_data->share_status = json_encode($shareNewData);
				$this->Users->save($user_data);

			}

			/*	if($setngs[0]['Sitesetting']['gmail_smtp'] == 'enable'){
					$this->Email->smtpOptions = array(
						'port' => $setngs[0]['Sitesetting']['smtp_port'],
						'timeout' => '30',
						'host' => 'ssl://smtp.gmail.com',
						'username' => $setngs[0]['Sitesetting']['noreply_email'],
						'password' => $setngs[0]['Sitesetting']['noreply_password']);

					$this->Email->delivery = 'smtp';
				}
				$this->Email->to = $emailaddress;
				$this->Email->subject = $setngs[0]['Sitesetting']['site_name']." Your order #".$orderid." shipment was delivered";
				$this->Email->from = SITE_NAME."<".$setngs[0]['Sitesetting']['noreply_email'].">";
				$this->Email->sendAs = "html";
				$this->Email->template = 'deliveredmail';
				$this->set('name', $name);
				$this->set('urlname', $urlname);
				$this->set('email', $emailaddress);
				$this->set('username',$username);
				$this->set('orderid',$orderid);
				$this->set('buyername',$buyername);
				$this->set('buyerurl',$buyerurl);
				$this->set('itemname',$itemname);
				$this->set('tot_quantity',$totquantity);
				$this->set('sizeopt',$custmrsizeopt);
				$this->set('access_url',SITE_URL."login");
				$this->set('orderdate',$orderModel['Orders']['orderdate']);
				$this->set('usershipping_addr',$usershipping_addr);
				$this->set('totalcost',$orderModel['Orders']['totalcost']);
				$this->set('currencyCode',$orderModel['Orders']['currency']);

				$this->Email->send();*/

				echo '{"status":"true","result":"Status changed to Delivered"}';
			} else if ($status == 'Cancel') {
				$this->loadModel('Items');
				$this->loadModel('Order_items');

			$orders = $this->Orders->find()->where(['orderid' => $orderid])->first();//Byorderid($orderid);
			if (count($orders) == 0) {
				echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
				die;
			}

			$order_status = $orders['status'];

			$logusrid = $orders['userid'];
			$userid = $orders['merchant_id'];
			if ($order_status == "" || $order_status == "Pending" || $order_status == "Processing") {
				$orderitemModel = $this->Order_items->find()->where(['orderid' => $orderid])->all();
				foreach ($orderitemModel as $order_item) 
				{
					$order_datas = $this->Order_items->find()->where(['orderItemid' => $order_item['orderItemid']])->first();
					$itemid = $order_datas['itemid'];
					$itemsize = $order_datas['item_size'];
					$itemquantity = $order_datas['itemquantity'];
					$item_datas = $this->Items->find()->where(['id' => $itemid])->first();
					$size_option = $item_datas['size_options'];
					$sizes = json_decode($size_option, true);
					if (!empty($sizes)) {
						$sizes['unit'][$itemsize] = $sizes['unit'][$itemsize] + $itemquantity;
						$sizeoptions = json_encode($sizes);
						$item_datas->size_options = $sizeoptions;
					}
					$updated_qnty = $item_datas['quantity'] + $itemquantity;
					$item_datas->quantity = $updated_qnty;
					$this->Items->save($item_datas);
				}

				/* update the 'cancelled' status in invoices */
				$invoice_datas = $this->Invoiceorders->find()->where(['orderid' => $orderid])->first();
				$invoiceid = $invoice_datas['invoiceid'];
				$this->Orders->updateAll(array('status' => "Cancelled", 'status_date' => $statusDate), array('orderid' => $orderid));

				echo '{"status":"true","result":"Status changed to cancelled"}';
			}
		} else if ($status == 'Returned') {

			$this->loadModel('Trackdetails');
			$orders = $this->Orders->find()->where(['orderid' => $orderid])->first();
			if (count($orders) == 0) {
				echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
				die;
			}

			$logusrid = $orders['userid'];
			$userid = $orders['merchant_id'];

			$shippingdate = $_POST['shipping_date'];
			$couriername = $_POST['courier_name'];
			$courierservice = $_POST['courier_service'];
			$trackid = $_POST['track_id'];
			$notes = $_POST['notes'];
			$reason = $_POST['reason'];
			$id = $_POST['id'];
			$track_data = $this->Trackdetails->newEntity();
			$track_data->orderid = $orderid;
			$track_data->shippingdate = $shippingdate;
			$track_data->couriername = $couriername;
			$track_data->courierservice = $courierservice;
			$track_data->trackingid = $trackid;
			$track_data->notes = $notes;
			$track_data->reason = $reason;

			if ($id != 0) {
				$track_data->id = $id;
			}
			$this->Trackdetails->save($track_data);

			$this->Orders->updateAll(array('status' => "Returned"), array('orderid' => $orderid));

			echo '{"status":"true","result":"Status changed to returned"}';
		} else if ($status == 'Claim') {

			$orders = $this->Orders->find()->where(['orderid' => $orderid])->first();
			$logusrid = $orders['userid'];
			$userid = $orders['merchant_id'];

			$this->Orders->updateAll(array('status' => "Claimed", 'status_date' => $statusDate), array('orderid' => $orderid));

			echo '{"status":"true","result":"Status changed to claimed"}';
		}
		$loguser = $this->Users->find()->where(['id' => $logusrid])->first();
		$logusernameurl = $loguser[0]['username_url'];
		$logusername = $loguser[0]['first_name'];
		$image['user']['image'] = $loguser[0]['profile_image'];
		$image['user']['link'] = SITE_URL . "people/" . $logusernameurl;
		$loguserimage = json_encode($image);
		if ($status == 'Delivered') {
			$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logusername . "</a>";
			$notifymsg = 'Your order ' . $orderid . ' has been received by the buyer-___- ' . $loguserlink;
		} elseif ($status == 'Processing') {
			$orderLink = '<a href="' . SITE_URL . 'buyerorderdetails/' . $orderid . '">view order: ' . $orderid . '</a>';
			$notifymsg = 'Your order has been marked as processing-___- ' . $orderLink;
		} elseif ($status == "Track") {
			$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logusername . "</a>";
			$orderLink = '<a href="' . SITE_URL . 'buyerorderdetails/' . $orderid . '">view order: ' . $orderid . '</a>';
			$notifymsg = 'Your order has been updated with Tracking details-___- ' . $orderLink;
		} elseif ($status == "Cancel") {
			$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logusername . "</a>";
			$orderLink = '<a href="' . SITE_URL . 'buyerorderdetails/' . $orderid . '">view order: ' . $orderid . '</a>';
			$notifymsg = 'Your order has been cancelled';
		} elseif ($status == "Paid") {
			$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logusername . "</a>";
			$orderLink = '<a href="' . SITE_URL . 'buyerorderdetails/' . $orderid . '">view order: ' . $orderid . '</a>';
			$notifymsg = 'Your order has been paid-___- ' . $orderLink;
		} elseif ($status == "Returned") {
			$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logusername . "</a>";
			$orderLink = '<a href="' . SITE_URL . 'buyerorderdetails/' . $orderid . '">view order: ' . $orderid . '</a>';
			$notifymsg = 'Your order has been returned-___- ' . $orderLink;
		} elseif ($status == "Claim") {
			$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logusername . "</a>";
			$orderLink = '<a href="' . SITE_URL . 'buyerorderdetails/' . $orderid . '">view order: ' . $orderid . '</a>';
			$notifymsg = 'Your order has been claimed-___- ' . $orderLink;
		}

		$logdetails = $this->addlog('orderstatus', $logusrid, $userid, $orderid, $notifymsg, null, $loguserimage);
		die;

	}

	function getatuserSearch()
	{

		$this->autoLayout = false;
		$this->autoRender = false;
		$searchWord = $_POST['key'];
		$this->loadModel('Users');
		$this->loadModel('Tempaddresses');
		$this->loadModel('Sitesettings');
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}
		if ($searchWord != null) {
			$userModel = $this->Users->find()->where(['username LIKE' => '%' . $searchWord . '%', ['user_level ' => 'normal']])->andWhere(['activation <>' => 0])->all();

			$userContent = array();
			if (count($userModel) != 0) {
				foreach ($userModel as $userkey => $user) {
					$userImage = $user['profile_image'];
					if (empty($userImage))
						$userImage = "usrimg.jpg";

					$userImage = $img_path . "media/avatars/thumb70/" . $userImage;
					$userContent[$userkey]['user_id'] = $user['id'];
					$userContent[$userkey]['user_name'] = $user['username'];
					$userContent[$userkey]['username_url'] = $user['username_url'];
					$userContent[$userkey]['user_image'] = $userImage;
					$userContent[$userkey]['full_name'] = $user['first_name'];
					$userContent[$userkey]['email'] = $user['email'];
					$shippingId = $user['defaultshipping'];
					$shippingDetail = $this->Tempaddresses->find()->where(['shippingid' => $shippingId])->first();
					$userContent[$userkey]['address1'] = $shippingDetail['address1'];
					$userContent[$userkey]['address2'] = $shippingDetail['address2'];
					$userContent[$userkey]['city'] = $shippingDetail['city'];
					$userContent[$userkey]['state'] = $shippingDetail['state'];
					$userContent[$userkey]['zipcode'] = $shippingDetail['zipcode'];
					$userContent[$userkey]['country'] = $shippingDetail['country'];
					$userContent[$userkey]['phone_no'] = $shippingDetail['phone'];

				}
				$resultArray = json_encode($userContent);
				echo '{"status":"true","result":' . $resultArray . '}';
				die;
			} else {
				echo '{"status":"false","message":"No match found"}';
				die;
			}
		} else {
			echo '{"status":"false","message":"Search word is Empty"}';
			die;
		}

	}

	function hashtag()
	{

		$this->loadModel('Comments');
		$this->loadModel('Feedcomments');
		$this->loadModel('Likedusers');
		$this->loadModel('Users');
		$this->loadModel('Logs');
		$this->loadModel('Items');
		$this->loadModel('Photos');
		$tagName = $_POST['key'];
		$offset = 0;
		$limit = 10;
		if (isset($_POST['offset'])) {
			$offset = $_POST['offset'];
		}
		if (isset($_POST['limit'])) {
			$limit = $_POST['limit'];
		}
		$this->loadModel('Sitesettings');
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}

		if (!empty($_POST['offset'])) {
			$commentModel = $this->Comments->find('all', array(
				'conditions' => array(
					'comments LIKE' => '%#%>' . $tagName . '<%',
					'item_id <>' => -1,
				),

				'limit' => $limit,
				'offset' => $_POST['offset'],
				'order' => 'id DESC',
			));
			$feedcommentModel = $this->Feedcomments->find('all', array(
				'conditions' => array(
					'comments LIKE' => '%#%>' . $tagName . '<%',
				),

				'limit' => $limit,
				'offset' => $_POST['offset'],
				'order' => 'id DESC',
			));

			$statusModel = $this->Comments->find('all', array(
				'conditions' => array(
					'comments LIKE' => '%#%>' . $tagName . '<%',
					'item_id' => -1,
				),

				'limit' => $limit,
				'offset' => $_POST['offset'],
				'order' => 'id DESC',
			));

		} else {

			$commentModel = $this->Comments->find('all', array(
				'conditions' => array(
					'comments LIKE' => '%#%>' . $tagName . '<%',
					'item_id <>' => -1,
				),

				'limit' => $limit,
				'order' => 'id DESC',
			));
			$feedcommentModel = $this->Feedcomments->find('all', array(
				'conditions' => array(
					'comments LIKE' => '%#%>' . $tagName . '<%',
				),

				'limit' => $limit,
				'order' => 'id DESC',
			));
			$statusModel = $this->Comments->find('all', array(
				'conditions' => array(
					'comments LIKE' => '%#%>' . $tagName . '<%',
					'item_id' => -1,
				),

				'limit' => $limit,
				'order' => 'id DESC',
			));
		}

			//$commentModel = $this->Comments->find('all',array('conditions'=>array(
				//	'comments like'=>'%#%>'.$tagName.'<%'),'group'=>'Comment.id','order'=>'Comment.id DESC',
				//	'offset'=>$offset,'limit'=>$limit));

		if (SITE_URL == $_SESSION['media_url']) {
			$img_path = $_SESSION['media_url'];
		} else {
			$img_path = $_SESSION['media_url'];
		}
		$output['hashtag'] = array();

		if (count($commentModel->toArray()) != 0) {

			foreach ($commentModel as $key => $comment) {

				$user_data = $this->Users->find()->where(['id' => $comment['user_id']])->first();
				$output['hashtag'][$key]['type'] = "comment";
				$output['hashtag'][$key]['user_id'] = $user_data['id'];
				$output['hashtag'][$key]['full_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
				$output['hashtag'][$key]['user_name'] = $user_data['username'];

				$profileimage = $user_data['profile_image'];
				if ($profileimage == "") {
					$profileimage = "usrimg.jpg";
				}
				$output['hashtag'][$key]['user_image'] = $img_path . 'media/avatars/thumb70/' . $profileimage;

				$item_datas = $this->Items->find()->where(['id' => $comment['item_id']])->first();//ById($comment['Item']['id']);
					//print_r($item_datas);
				$photo = $this->Photos->find()->where(['item_id' => $comment['item_id']])->first();
				$userimage = $photo['image_name'];
				if ($userimage == "") {
					$userimage = "usrimg.jpg";
				}
				$output['hashtag'][$key]['item_image'] = $img_path . 'media/items/thumb70/' . $userimage;
				$output['hashtag'][$key]['item_id'] = $item_datas['id'];
				$output['hashtag'][$key]['item_title'] = $item_datas['item_title'];

				$output['hashtag'][$key]['comment'] = $comment['comments'];
				$log_data = $this->Logs->find()->where(['sourceid' => $comment['id']])->first();
				$output['hashtag'][$key]['date'] = $log_data['cdate'];
			}

		}

		if (count($feedcommentModel->toArray()) != 0) {
			$output1['hashtag1'] = array();
			foreach ($feedcommentModel as $key => $feedcomment) {
				$user_data = $this->Users->find()->where(['id' => $feedcomment['userid']])->first();
				$output1['hashtag1'][$key]['type'] = "feed_comment";

				$output1['hashtag1'][$key]['user_id'] = $user_data['id'];
				$output1['hashtag1'][$key]['full_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
				$output1['hashtag1'][$key]['user_name'] = $user_data['username'];

				$profileimage = $user_data['profile_image'];
				if ($profileimage == "") {
					$profileimage = "usrimg.jpg";
				}
				$output1['hashtag1'][$key]['user_image'] = $img_path . 'media/avatars/thumb70/' . $profileimage;
				$output1['hashtag1'][$key]['feed_id'] = $feedcomment['statusid'];
				$log_data = $this->Logs->find()->where(['id' => $feedcomment['statusid']])->first();
				$output1['hashtag1'][$key]['message'] = $log_data['message'];
				$status_values = json_decode($log_data['image'], true);
				$status_image = $status_values['status']['image'];

				if ($status_image)
					$output1['hashtag1'][$key]['status_image'] = $status_image;
				else
					$output1['hashtag1'][$key]['status_image'] = "";

				$image = $status_image;
				list($width, $height) = getimagesize($image);
				if (empty($height))
					$height = "350";
				if (empty($width))
					$width = "350";
				$output1['hashtag1'][$key]['height'] = $height;
				$output1['hashtag1'][$key]['width'] = $width;

				$output1['hashtag1'][$key]['comment'] = $feedcomment['comments'];
				$output1['hashtag1'][$key]['date'] = $log_data['cdate'];
			}

		}

		if (count($statusModel->toArray()) != 0) {
			$output2['hashtag2'] = array();
			foreach ($statusModel as $key => $status) {
				$output2['hashtag2'][$key]['type'] = "status";
				$log_data = $this->Logs->find()->where(['sourceid' => $status['id']])->andWhere(['type' => 'status'])->first();
				$output2['hashtag2'][$key]['message'] = $log_data['message'];
				$status_values = json_decode($log_data['image'], true);
				$status_image = $status_values['status']['image'];

				if ($status_image)
					$output2['hashtag2'][$key]['status_image'] = $status_image;
				else
					$output2['hashtag2'][$key]['status_image'] = "";

				$image = $status_image;
				list($width, $height) = getimagesize($image);
				if (empty($height))
					$height = "350";
				if (empty($width))
					$width = "350";
				$output2['hashtag2'][$key]['height'] = $height;
				$output2['hashtag2'][$key]['width'] = $width;
				$logid = $log_data['id'];
				$feedfollowers = $this->Likedusers->find()->where(['statusid' => $logid])->all();//',array('conditions'=>array('Likedusers.statusid'=>$logid)));
							//echo count($feedfollowers);
				$followinguserids = array();
				foreach ($feedfollowers as $ffollowers) {
					$followinguserids[] = $ffollowers['userid'];
				}
				if (in_array($status['user_id'], $followinguserids)) {
					$output2['hashtag2'][$key]['liked'] = "yes";
				} else {
					$output2['hashtag2'][$key]['liked'] = "no";
				}
				$output2['hashtag2'][$key]['likes_count'] = $log['likecount'];
				$output2['hashtag2'][$key]['comment_count'] = $log['commentcount'];
				$user_data = $this->Users->find()->where(['id' => $status['user_id']])->first();

				$output2['hashtag2'][$key]['user_id'] = $user_data['id'];
				$output2['hashtag2'][$key]['full_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
				$output2['hashtag2'][$key]['user_name'] = $user_data['username'];

				$profileimage = $user_data['profile_image'];
				if ($profileimage == "") {
					$profileimage = "usrimg.jpg";
				}
				$output2['hashtag2'][$key]['user_image'] = $img_path . 'media/avatars/thumb70/' . $profileimage;
				$output2['hashtag2'][$key]['date'] = $log_data['cdate'];

			}

		}
		$temp = array();
		if ($output['hashtag'] == "")
			$output['hashtag'] = array();
		if ($output1['hashtag1'] == "")
			$output1['hashtag1'] = array();
		if ($output2['hashtag2'] == "")
			$output2['hashtag2'] = array();
		$temp = array_merge($output['hashtag'], $output1['hashtag1'], $output2['hashtag2']);

		if (empty($temp)) {
			echo '{"status": "false", "message": "No data found"}';
			die;
		}
		$resultArray = json_encode($temp);
		echo '{"status":"true","result":' . $resultArray . '}';
		die;

	}

	function cart()
	{
// /echo 'cart'; die;
		$this->loadModel('Carts');
		$this->loadModel('Items');
		$this->loadModel('Users');
		$this->loadModel('Tempaddresses');
		$this->loadModel('Shipings');
		$this->loadModel('Photos');
		$this->loadModel('Shops');
		$this->loadModel('Taxes');
		$this->loadModel('Forexrates');
		$item_id = $_POST['item_id'];
		$user_id = $_POST['user_id'];
		$shipping_id = $_POST['shipping_id'];
		$size = $_POST['size'];
		$total_cost = 0;
		$ship_cost = 0;
		$tdy = strtotime(date("Y-m-d"));
		$cart_data = $this->Carts->find()->where(['user_id' => $user_id])->andWhere(['item_id' => $item_id])->first();
		$item_data = $this->Items->find()->where(['id' => $item_id])->first();
		$shop_data = $this->Shops->find()->where(['id' => $item_data['shop_id']])->first();
		$user_data = $this->Users->find()->where(['id' => $user_id])->first();
		$setngs = $this->Sitesettings->find()->toArray();
		$default_currency = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();
		$currency_value = $this->Forexrates->find()->where(['id' => $user_data['currencyid']])->first();


		if ($currency_value['currency_code'] == $default_currency['currency_code'] || $currency_value['currency_code'] == "") {
			$cur = $default_currency['price'];
			$cur_symbol = $default_currency['currency_symbol'];
		} else {

			$cur = $currency_value['price'];
			$cur_symbol = $currency_value['currency_symbol'];
		}

		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}

		/* SHIPPING ADDRESS */
		if ($shipping_id == 0) {
			$defaultAddress = $user_data['defaultshipping'];
			$shipping_address = $this->Tempaddresses->find()->where(['shippingid' => $defaultAddress])->andWhere(['userid' => $user_id])->first();

		} else {
			$defaultAddress = $_POST['shipping_id'];
			$shipping_address = $this->Tempaddresses->find()->where(['shippingid' => $defaultAddress])->first();

		}


		if (SITE_URL == $_SESSION['media_url']) {
			$img_path = $_SESSION['media_url'];
		} else {
			$img_path = $_SESSION['media_url'];
		}

		if ($item_id == 0){
			$cartModel = $this->Carts->find()->where(['user_id' => $user_id, ['payment_status' => 'progress']])->all();
		}
		else{
			//echo 'test'; die;
			$res = $this->itemdet($_POST['user_id'], $_POST['item_id'], $defaultAddress);
				//$cartModel = $this->Carts->find()->where(['user_id'=>$user_id,['payment_status'=>'progress']])->andWhere(['item_id'=>$item_id])->all();
		}


		if (count($cartModel) == 0) {
				//$res= $this->itemdet($_POST['user_id'],$_POST['item_id'],$defaultAddress);
			echo '{"status": "false", "message": "No data found"}';
			die;
		}

		$resultarray['items'] = array();
		$c = count($cartModel);
		$i = 1;
		$shoprooms = array();
		$itemRooms = array();
		$tdy = strtotime(date("Y-m-d"));

		//echo '<pre>'; print_r($cartModel); die;

		foreach ($cartModel as $key => $cart) {
			$temp[] = '';
			$itemIds = $cart['item_id'];
			if ($_POST['size'] == "") {
				$size = $cart['size_options'];
			}
			$item_datas = TableRegistry::get('Items')->find()->contain('Forexrates')->where(['Items.id' => $itemIds])->first();

			$process_time = $item_datas['processing_time'];
			if ($process_time == '1d') {
				$process_time = "One business day";
			} elseif ($process_time == '2d') {
				$process_time = "Two business days";
			} elseif ($process_time == '3d') {
				$process_time = "Three business days";
			} elseif ($process_time == '4d') {
				$process_time = "Four business days";
			} elseif ($process_time == '2ww') {
				$process_time = "One-Two weeks";
			} elseif ($process_time == '3w') {
				$process_time = "Two-Three weeks";
			} elseif ($process_time == '4w') {
				$process_time = "Three-Four weeks";
			} elseif ($process_time == '6w') {
				$process_time = "Four-Six weeks";
			} elseif ($process_time == '8w') {
				$process_time = "Six-Eight weeks";
			}

			$resultarray['items'][$key]['cart_id'] = $cart['id'];
			$resultarray['items'][$key]['item_id'] = $item_datas['id'];
			$resultarray['items'][$key]['item_name'] = $item_datas['item_title'];
			$photo = $this->Photos->find()->where(['item_id' => $item_datas['id']])->first();
			if ($photo['image_name'] == "") {
				$itemImage = "usrimg.jpg";
			} else {
				$itemImage = $photo['image_name'];
			}
			$resultarray['items'][$key]['item_image'] = $img_path . 'media/items/thumb350/' . $itemImage;
			
			$tdy = strtotime(date("Y-m-d"));

			if (strtotime($item_datas['dealdate']) == $tdy && $item_datas['discount_type'] == 'daily') {
				$resultarray['items'][$key]['deal_enabled'] = 'yes';
				$resultarray['items'][$key]['pro_discount'] = 'dailydeal';
				$resultarray['items'][$key]['discount_percentage'] = $item_datas['discount'];
			} elseif($item_datas['discount_type'] == 'regular') {
				$resultarray['items'][$key]['deal_enabled'] = 'yes';
				$resultarray['items'][$key]['pro_discount'] = 'regulardeal';
				$resultarray['items'][$key]['discount_percentage'] = $item_datas['discount'];
			}else{
				$resultarray['items'][$key]['deal_enabled'] = 'no';
				$resultarray['items'][$key]['discount_percentage'] = 0;
			}

			/*
			if (strtotime($item_datas['dealdate']) == $tdy && $item_datas['dailydeal'] == 'yes') {
				$resultarray['items'][$key]['deal_enabled'] = "yes";
				$resultarray['items'][$key]['discount_percentage'] = $item_datas['discount'];
			} else {
				$resultarray['items'][$key]['deal_enabled'] = "no";
				$resultarray['items'][$key]['discount_percentage'] = 0;
			}
			*/


			if ($size != "") {
				//echo 'test'; die;
				$sizeoptions = $item_datas['size_options'];
				$sizes = json_decode($sizeoptions, true);
				if (!empty($sizes)) {
					$sizeoptions = $item_datas['size_options'];
					$sizes = json_decode($sizeoptions, true);
					$price = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $sizes['price'][$size]);
					$price = round($price, 2);
					if ($item_datas['discount_type'] == 'daily' && strtotime($item_datas['dealdate']) == $tdy) {
						$dailydealdiscount = $item_datas['discount'];
						$unitPriceConvert = number_format((float)$price, 2, '.', '');
						$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);
						if ($daily_price != "") {
							$pricetot += number_format((float)$daily_price, 2, '.', '');
						}
						$resultarray['items'][$key]['price'] = $daily_price;
					} elseif($item_datas['discount_type'] == 'regular') {
					$dailydealdiscount = $item_datas['discount'];
					$unitPriceConvert = number_format((float)$price, 2, '.', '');
					$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);
					if ($daily_price != "") {
						$pricetot += number_format((float)$daily_price, 2, '.', '');
					}
					$resultarray['items'][$key]['price'] = number_format((float)$daily_price, 2, '.', '');
					}else{
						$resultarray['items'][$key]['price'] = $price;
					}
					$quantity = $sizes['unit'][$size];
				}
				$resultarray['items'][$key]['mainprice'] = $price;
				$resultarray['items'][$key]['size'] = $cart['size_options'];
			} else {

				$price = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $item_datas['price']);
				$price = round($price, 2);
				if ($item_datas['discount_type'] == 'daily' && strtotime($item_datas['dealdate']) == $tdy) {
					$dailydealdiscount = $item_datas['discount'];
					$unitPriceConvert = number_format((float)$price, 2, '.', '');
					$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);
					if ($daily_price != "") {
						$pricetot += number_format((float)$daily_price, 2, '.', '');
					}
					$resultarray['items'][$key]['price'] = number_format((float)$daily_price, 2, '.', '');
				} elseif($item_datas['discount_type'] == 'regular') {
					$dailydealdiscount = $item_datas['discount'];
					$unitPriceConvert = number_format((float)$price, 2, '.', '');
					$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);
					if ($daily_price != "") {
						$pricetot += number_format((float)$daily_price, 2, '.', '');
					}
					$resultarray['items'][$key]['price'] = number_format((float)$daily_price, 2, '.', '');
				}else{
					$resultarray['items'][$key]['price'] = $price;
				}
				
				
				$resultarray['items'][$key]['mainprice'] = $price;
				$resultarray['items'][$key]['size'] = "";
				$quantity = $item_datas['quantity'];
			}
			$resultarray['items'][$key]['quantity'] = $cart['quantity'];
			$resultarray['items'][$key]['total_quantity'] = $quantity;
			$resultarray['items'][$key]['shipping_time'] = $process_time;
			if (strtotime($item_datas['dealdate']) == $tdy && $item_datas['dailydeal'] == 'yes') {
				$resultarray['items'][$key]['deal_enabled'] = "yes";
				$resultarray['items'][$key]['discount_percentage'] = $item_datas['discount'];
			} else {
				$resultarray['items'][$key]['deal_enabled'] = "no";
				$resultarray['items'][$key]['discount_percentage'] = 0;
			}
			$resultarray['items'][$key]['fbshare_discount'] = $item_datas['share_discountAmount'];
			$shop_data = $this->Shops->find()->where(['id' => $item_datas['shop_id']])->first();
			$resultarray['items'][$key]['store_id'] = $shop_data['id'];
			$resultarray['items'][$key]['store_name'] = $shop_data['shop_name'];
			$temp[$item_datas['shop_id']][$key] = $resultarray['items'][$key]['price'] * $cart['quantity'] . ',' . $item_datas['id'];
			$total_cost += $resultarray['items'][$key]['quantity'] * $resultarray['items'][$key]['price'];
			$resultarray1['item_total'] = number_format((float)$total_cost, 2, '.', '');

			/* TOTAL SELLER AMOUNT */
			$shopamount[$item_datas['shop_id']] += $resultarray['items'][$key]['quantity'] * $resultarray['items'][$key]['price'];

			if (!in_array($item_datas['shop_id'], $shoprooms)) {
				array_push($shoprooms, $item_datas['shop_id']);
			}

			$shiping = $this->Shipings->find()->where(['item_id' => $item_datas['id']])->andWhere(['country_id' => $shipping_address['countrycode']])->first();

			if (count($shiping) == 0) {
				$shiping = $this->Shipings->find()->where(['item_id' => $item_datas['id']])->andWhere(['country_id' => 0])->first();

			}

			$shipingprice = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $shiping['primary_cost']);
			$shipingprice = round($shipingprice, 2);

			/* shipping price once for same size */
			if (!in_array($item_datas['id'], $itemRooms)) {
				array_push($itemRooms, $item_datas['id']);
				$shippingamount[$item_datas['shop_id']] += $shipingprice;
			}

			$postalcode = json_decode($shop_data['postalcodes'], true);
			if (in_array($shipping_address['zipcode'], $postalcode)) {
				$ship_cost = 0;
				$shippingamount[$item_datas['shop_id']] = $ship_cost;
			}

			if ($i == $c) {
				for ($j = 0; $j < count($shoprooms); $j++) {
					$shop_det = $this->Shops->find()->where(['id' => $shoprooms[$j]])->first();
					$shopCurrencyDetails = $this->Forexrates->find()->where(['currency_code' => $shop_det['currency']])->first();
					$amt = $shopamount[$shoprooms[$j]];
					$freeamt = $this->Currency->conversion($shopCurrencyDetails['price'], $cur, $shop_det['freeamt']);

					if ($amt >= $freeamt && $freeamt > 0) {
						$shippingamount[$shoprooms[$j]] = 0;
					}
				}
			}
			$resultarray1['shipping_price'] = array_sum($shippingamount);
			$cartQuantity = $cart["quantity"];
			
			if ($quantity == 0 || $item_datas['status'] != "publish" || count($shiping) == 0 || count($shipping_address) == 0 || ($cartQuantity>$quantity)) {
				$buyable = "no";
			} else {
				$buyable = "yes";
			}

			$resultarray['items'][$key]['buyable'] = $buyable;
			if ($buyable == "no") {
				if ($quantity == 0)
					$cart_message = "Item sold out";
				elseif ($item_datas['status'] != "publish")
					$cart_message = "Item disabled";
				elseif (count($shipping_address) == 0)
					$cart_message = "Shipping not available for this country";
				elseif (count($shiping) == 0)
					$cart_message = "Shipping not available for this country";
				elseif(($cartQuantity>$quantity))
					$cart_message = "Requested Quantity Not Available";
			} else
			$cart_message = "";

			if ($i == $c) {
				$tax_datas = $this->Taxes->find()->where(['countryid' => $shipping_address['countrycode']])->andWhere(['status' => 'enable'])->all();

				foreach ($tax_datas as $taxes) {
					$tax_cost += $taxes['percentage'];
				}
			}
			$resultarray['items'][$key]['cart_message'] = $cart_message;
			$resultarray1['tax'] = round(($tax_cost * $total_cost) / 100, 2);
			$grand_val_total = $resultarray1['item_total'] + $resultarray1['shipping_price'] + $resultarray1['tax'];
			$resultarray1['grand_total'] = number_format((float)$grand_val_total, 2, '.', '');
			$resultarray1['currency'] = $cur_symbol;
			$resultarray1['items'] = $resultarray['items'];
			$resultarray1['shipping']['shipping_id'] = $shipping_address['shippingid'];
			$user_detail = $this->Users->find()->where(['id' => $shipping_address['userid']])->first();
				//$resultarray1['shipping']['full_name'] = $user_detail['first_name']. ' '.$user_detail['last_name'];
			$resultarray1['shipping']['full_name'] = $shipping_address['name'];
			$resultarray1['shipping']['nick_name'] = $shipping_address['nickname'];
			$resultarray1['shipping']['address1'] = $shipping_address['address1'];
			$resultarray1['shipping']['address2'] = $shipping_address['address2'];
			$resultarray1['shipping']['city'] = $shipping_address['city'];
			$resultarray1['shipping']['state'] = $shipping_address['state'];
			$resultarray1['shipping']['country'] = $shipping_address['country'];
			$resultarray1['shipping']['country_id'] = $shipping_address['countrycode'];
			$resultarray1['shipping']['zipcode'] = $shipping_address['zipcode'];
			$resultarray1['shipping']['phone'] = $shipping_address['phone'];
			if ($shipping_address['shippingid'] == $user_detail['defaultshipping'])
				$resultarray1['shipping']['default'] = "yes";
			else
				$resultarray1['shipping']['default'] = "no";
			$i++;

		}
		echo '{"status":"true","result":' . json_encode($resultarray1) . '}';
		die;
	}

	function contactSellerMessage()
	{

		$this->loadModel('Contactsellers');
		$this->loadModel('Contactsellermsgs');
		$this->loadModel('Shops');
		$this->loadModel('Items');
		$this->loadModel('Users');
		$this->loadModel('Photos');
		$this->loadModel('Sitesettings');
		$setngs = $this->Sitesettings->find()->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}
		$user_id = $_POST['user_id'];

		$search_key = $_POST['search_key'];
		$resultarray = array();
		if (isset($_POST['limit'])) {
			$limit = $_POST['limit'];
		} else {
			$limit = 10;
		}
		if (isset($_POST['offset'])) {
			$contactseller_data = $this->Contactsellers->find('all', array(
				'conditions' => array(
					'buyerid' => $user_id,
					'or' => array(
						'itemname LIKE' => '%' . $search_key . '%',
						'sellername LIKE' => '%' . $search_key . '%'
					)
    			//'itemname LIKE'=>'%'.$search_key.'%',
				),

				'limit' => $limit,
				'offset' => $_POST['offset'],
				'order' => 'lastmodified DESC',
			));

		} else {
			$contactseller_data = $this->Contactsellers->find('all', array(
				'conditions' => array(
					'buyerid' => $user_id,
					'or' => array(
						'itemname LIKE' => '%' . $search_key . '%',
						'sellername LIKE' => '%' . $search_key . '%'
					)
    			//'itemname LIKE'=>'%'.$search_key.'%',
				),

				'limit' => $limit,
				'order' => 'lastmodified DESC',
			));
		}
			//$contactseller_data = $this->Contactsellers->find()->where(['buyerid'=>$user_id])->all();
		foreach ($contactseller_data as $key => $contactseller_datas) {
			$resultarray[$key]['chat_id'] = $contactseller_datas['id'];
			$item_data = $this->Items->find()->where(['id' => $contactseller_datas['itemid']])->first();
			$resultarray[$key]['item_title'] = $item_data['item_title'];
			$resultarray[$key]['item_id'] = $item_data['id'];
			$photo = $this->Photos->find()->where(['item_id' => $item_data['id']])->first();
			if ($photo['image_name'] == "") {
				$itemImage = "usrimg.jpg";
			} else {
				$itemImage = $photo['image_name'];
			}
			$resultarray[$key]['image'] = $img_path . 'media/items/thumb350/' . $itemImage;
			$resultarray[$key]['subject'] = $contactseller_datas['subject'];
			$message = $this->Contactsellermsgs->find()->where(['contactsellerid' => $contactseller_datas['id']])->order('id DESC')->first();
			$resultarray[$key]['message'] = $message['message'];
			$resultarray[$key]['chat_date'] = $message['createdat'];
			$shop_data = $this->Shops->find()->where(['user_id' => $contactseller_datas['merchantid']])->first();
			$resultarray[$key]['shop_id'] = $shop_data['id'];
			$resultarray[$key]['shop_name'] = $shop_data['shop_name'];
			if ($shop_data[$key]['shop_image'] == "") {
				$shopImage = "usrimg.jpg";
			} else {
				$shopImage = $shop_data['shop_image'];
			}
			$resultarray[$key]['shop_image'] = $img_path . 'media/avatars/thumb350/' . $shopImage;
			if ($contactseller_datas['lastsent'] == "seller" && $contactseller_datas['buyerread'] == 1)
				$resultarray[$key]['last_replied'] = $contactseller_datas['merchantid'];
			else
				$resultarray[$key]['last_replied'] = 0;

		}
		if (!empty($resultarray)) {
			echo '{"status": "true", "result": ' . json_encode($resultarray) . '}';
			die;

		} else {
			echo '{"status": "false", "message": "No data found"}';
			die;

		}
	}

	function sendComments()
	{
		$this->loadModel('Comments');
		$this->loadModel('Items');
		$this->loadModel('Users');
		$this->loadModel('Hashtags');
		$this->loadModel('Sitesettings');
		$this->loadModel('Photos');
		$this->loadModel('Itemfavs');
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
		$userstable = TableRegistry::get('Users');
		if (SITE_URL == $_SESSION['media_url']) {
			$img_path = $_SESSION['media_url'];
		} else {
			$img_path = $_SESSION['media_url'];
		}

		if (isset($_POST)) {
			$userId = $_POST['user_id'];
			$itemId = $_POST['item_id'];
			$pushcomment = $_POST['comment'] . " ";
			$comment = $_POST['comment'] . " ";
			$usedHashtag = '';
			$oldHashtags = array();
			$loguser = $this->Users->find()->where(['id' => $userId])->first();
			preg_match_all('/#([\S]*?)(?=\s)/', $comment, $hashmatch);
			if (!empty($hashmatch)) {
				foreach ($hashmatch[1] as $hashtag) {
					$cleanedHashtag = preg_replace('/[^A-Za-z0-9\-]/', '', $hashtag);
					if (!empty($cleanedHashtag) && $cleanedHashtag != '') {
						$hashtag = $cleanedHashtag;
						if ($usedHashtag == '') {
							$usedHashtag = $hashtag;
						}
						$usedHashtag .= ',' . $hashtag;
						$comment = str_replace('#' . $hashtag . " ", '<span class="hashatcolor">#</span><a href="' . SITE_URL . 'hashtag/' . $hashtag . '">' . $hashtag . '</a> ', $comment);
					}
				}

				$hashTags = explode(',', $usedHashtag);
				$hashtagsModel = $this->Hashtags->find()->where(['hashtag IN' => $hashTags])->all();
				if (!empty($hashtagsModel)) {
					foreach ($hashtagsModel as $hashtags) {
						$id = $hashtags['id'];
						$count = $hashtags['usedcount'] + 1;
						$this->Hashtags->updateAll(array('usedcount' => $count), array('id' => $id));
						$oldHashtags[] = $hashtags['hashtag'];
					}
				}
				foreach ($hashTags as $hashtag) {
					if (!in_array($hashtag, $oldHashtags)) {
						$hashtag_data = $this->Hashtags->newEntity();
						$hashtag_data->hashtag = $hashtag;
						$hashtag_data->usedcount = 1;
						$this->Hashtags->save($hashtag_data);
					}
				}
			}
			preg_match_all('/@([\S]*?)(?=\s)/', $comment, $atmatch);
			$mentionedUsers = "";
			if (!empty($atmatch)) {
				foreach ($atmatch[1] as $atuser) {
					$cleanedAtUser = preg_replace('/[^A-Za-z0-9\-]/', '', $atuser);
					if (!empty($cleanedAtUser) && $cleanedAtUser != '') {
						$atuser = $cleanedAtUser;
						$comment = str_replace('@' . $atuser . " ", '<span class="hashatcolor">@</span><a href="' . SITE_URL . 'people/' . $atuser . '">' . $atuser . '</a> ', $comment);
						$mentionedUsers = $mentionedUsers != "" ? "," . $atuser : $atuser;
					}
				}
			}
			$comment_data = $this->Comments->newEntity();
			$comment_data->user_id = $userId;
			$comment_data->item_id = $itemId;
			$comment_data->comments = $comment;
			$result = $this->Comments->save($comment_data);
			$resultArray = array();
			$resultArray['comment_id'] = $result->id;
			$id = $result->id;
			$userModel = $this->Users->find()->where(['id' => $userId])->first();
			$path = $img_path . "media/avatars/thumb70/";

			if (!empty($userModel["profile_image"])) {
				$path .= $userModel['profile_image'];
			} else {
				$path .= 'usrimg.jpg';
			}
			$commentEncoded = urldecode($pushcomment);
			$resultArray['comment'] = $commentEncoded;
			$resultArray['user_id'] = $userId;
			$resultArray['user_image'] = $path;
			$resultArray['user_name'] = $userModel['username_url'];
			$resultArray['full_name'] = $userModel['first_name'] . ' ' . $userModel['last_name'];

			$userdatasall = $this->Items->find()->where(['id' => $itemId])->first();//ById($itemId);
			$photo = $this->Photos->find()->where(['item_id' => $itemId])->first();

			if ($mentionedUsers != "") {
				$mentionedUsers = explode(",", $mentionedUsers);
				foreach ($mentionedUsers as $musers) {
					$userModel = $this->Users->find()->where(['username' => $musers])->first();
					$notifyModel = $userstable->find()->where(['username' => $musers])->first();
					$notificationSettings = json_decode($notifyModel['push_notifications'], true);
					$notifyto = $notifyModel['id'];
					if ($notificationSettings['somone_mentions_push'] == 1 && $userId != $notifyto) {
						$logusername = $loguser['username'];
						$logusernameurl = $loguser['username_url'];
						$itemname = $userdatasall['item_title'];
						$itemurl = $userdatasall['item_title_url'];
						$liked = $setngs[0]['liked_btn_cmnt'];
						if (!empty($loguser['profile_image'])) {
							$image['user']['image'] = $loguser['profile_image'];
						} else {
							$image['user']['image'] = 'usrimg.jpg';
						}
						$image['user']['link'] = SITE_URL . "people/" . $logusernameurl;
						$image['item']['image'] = $photo['image_name'];
						$image['item']['link'] = SITE_URL . "listing/" . $itemId . "/" . $itemurl;
						$loguserimage = json_encode($image);
						$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logusername . "</a>";
						$productlink = "<a href='" . SITE_URL . "listing/" . $itemId . "/" . $itemurl . "'>" . $itemname . "</a>";
						$notifymsg = $loguserlink . " -___-mentioned you in a comment on: -___- " . $productlink;
						$logdetails = $this->addlog('mentioned', $userId, $notifyto, $id, $notifymsg, $comment, $loguserimage, $itemId);

						/* Push Notifications & Logs */
						$userdevicestable = TableRegistry::get('Userdevices');
						$userddett = $userdevicestable->find('all')->where(['user_id' => $notifyto])->all();
						foreach ($userddett as $userd) {
							$deviceTToken = $userd['deviceToken'];
							$badge = $userd['badge'];
							$badge += 1;
							$querys = $userdevicestable->query();
							$querys->update()
							->set(['badge' => $badge])
							->where(['deviceToken' => $deviceTToken])
							->execute();

							$user_datas = TableRegistry::get('Users')->find()->where(['id' => $notifyto])->first();
							$user_profile_image = $user_datas['profile_image'];
							if ($user_profile_image == "")
								$user_profile_image = "usrimg.jpg";

							if (isset($deviceTToken)) {
								$pushMessage['type'] = 'mentioned';
								$pushMessage['user_id'] = $notifyto;
								$pushMessage['user_name'] = $user_datas['username'];
								$pushMessage['user_image'] = $user_profile_image;
								$user_detail = TableRegistry::get('Users')->find()->where(['id' => $notifyto])->first();
								I18n::locale($user_detail['languagecode']);
								$pushMessage['message'] = $logusername . " " . __d('user', "mentioned you in a comment on product") . " " . $itemname;
								$messages = json_encode($pushMessage);
								$this->pushnot($deviceTToken, $messages, $badge);
							}
						}
					}

				}
			}

			$favUsers = $this->Itemfavs->find()->where(['user_id' => $userId])->all();
			if (!empty($favUsers)) {
				foreach ($favUsers as $fuser) {
					$userModels = $this->Users->find()->where(['id' => $fuser['user_id']])->first();
					$notifyto = $userdatasall['id'];
					$notificationSettings = json_decode($userModels['push_notifications'], true);
					if ($notificationSettings['somone_cmnts_push'] == 1 && $userId != $notifyto) {
						$favnotifyto[] = $userModels['id'];
					}
				}
				$logusername = $loguser['username'];
				$logusernameurl = $loguser['username_url'];
				if (!empty($favnotifyto)) {
					$itemname = $userdatasall['item_title'];
					$itemurl = $userdatasall['item_title_url'];
					$liked = $setngs[0]['liked_btn_cmnt'];
					$image['user']['image'] = $loguser['profile_image'];
					$image['user']['link'] = SITE_URL . "people/" . $logusernameurl;
					$image['item']['image'] = $photo['image_name'];
					$image['item']['link'] = SITE_URL . "listing/" . $itemId . "/" . $itemurl;
					$loguserimage = json_encode($image);
					$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logusername . "</a>";
					$productlink = "<a href='" . SITE_URL . "listing/" . $itemId . "/" . $itemurl . "'>" . $itemname . "</a>";
					$notifymsg = $loguserlink . " -___-commented on-___- " . $productlink;
					$logdetails = $this->addlog('comment', $userId, $favnotifyto, $id, $notifymsg, $comment, $loguserimage);
				}
			}
			echo '{"status":"true","result":' . json_encode($resultArray) . '}';
			die;
		} else {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		}
	}

	function reportItem()
	{

		$this->loadModel('Items');

		if (!empty($_POST['item_id']) && !empty($_POST['user_id'])) {
			$itemId = $_POST['item_id'];
			$userId = $_POST['user_id'];

			$itemModel = $this->Items->find()->where(['id' => $itemId])->first();
			if (count($itemModel) == 0) {
				echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
				die;
			}

			if (!empty($itemModel['report_flag'])) {
				$reportFlag = json_decode($itemModel['report_flag'], true);

				$reportFlag[] = $userId;
				$itemModel->report_flag = json_encode($reportFlag);

			} else {
				$reportFlag[] = $userId;
				$itemModel->report_flag = json_encode($reportFlag);
			}
			if ($this->Items->save($itemModel)) {
				echo '{"status":"true","message":"Item Reported Successfully"}';
				die;
			} else {
				echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
				die;
			}
		} else {
			echo '{"status":"false","message":"Item id and User id are Invalid"}';
			die;
		}
	}

	function undoreportItem()
	{

		$this->loadModel('Items');

		if (!empty($_POST['item_id']) && !empty($_POST['user_id'])) {
			$itemId = $_POST['item_id'];
			$userId = $_POST['user_id'];

			$itemModel = $this->Items->find()->where(['id' => $itemId])->first();
			if (count($itemModel) == 0) {
				echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
				die;
			}
			if (!empty($itemModel['report_flag'])) {
				$reportFlag = json_decode($itemModel['report_flag'], true);
				$newreportflag = array();
				foreach ($reportFlag as $flag) {
					if ($flag != $userId) {
						$newreportflag[] = $flag;
					}
				}
				if (!empty($newreportflag)) {
					$itemModel->report_flag = json_encode($newreportflag);

				} else {
					$itemModel->report_flag = '';

				}
			}
			if ($this->Items->save($itemModel)) {
				echo '{"status":"true","message":"Item Unreported Successfully"}';
				die;
			} else {
				echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
				die;
			}

		} else {
			echo '{"status":"false","message":"Item id and User id are Invalid"}';
			die;
		}
	}

	function facebookShareDiscount()
	{

		$userId = $_POST['user_id'];
		$itemId = $_POST['item_id'];

		$this->loadModel('Facebookcoupons');
		$this->loadModel('Sellercoupons');
		$this->loadModel('Shops');
		$this->loadModel('Items');
		$generatevalue = $this->get_uniquecode('8');
		$itemDatas = $this->Items->find()->where(['id' => $itemId])->first();

		if (count($itemDatas) == 0) {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		}
		$couponPercent = $itemDatas['share_discountAmount'];
		$shopId = $itemDatas['shop_id'];
		$shop_data = $this->Shops->find()->where(['id' => $shopId])->first();
		$shopuserId = $shop_data['user_id'];
		$getcouponval = $this->Sellercoupons->find()->where(['couponcode' => $generatevalue])->first();
		$todayDate = date("Y-m-d");
		$lastDate = date("Y-m-d", strtotime("tomorrow"));
		$fbshare_detail = $this->Facebookcoupons->find()->where(['user_id' => $userId])->andWhere(['item_id' => $itemId])->all();
		if (count($fbshare_detail) == 0) {
			if (count($getcouponval) == 0) {

				$sellercoupons = $this->Sellercoupons->newEntity();
				$sellercoupons->type = 'facebook';
				$sellercoupons->couponcode = $generatevalue;
				$sellercoupons->couponpercentage = $couponPercent;
				$sellercoupons->validtodate = $lastDate;
				$sellercoupons->remainrange = 1;
				$sellercoupons->sellerid = $shopuserId;
				$sellercoupons->sourceid = $itemId; //Item is the source
				$sellercoupons->totalrange = 1;
				$sellercoupons->validfrom = $todayDate;
				$sellercoupons->validto = $lastDate;
				$result = $this->Sellercoupons->save($sellercoupons);
				$couponId = $result->id;

				$fbcoupons = $this->Facebookcoupons->newEntity();
				$fbcoupons->couponcode = $generatevalue;
				$fbcoupons->item_id = $itemId;
				$fbcoupons->user_id = $userId;
				$fbcoupons->coupon_id = $couponId;
				$fbcoupons->cdate = time();
				$result1 = $this->Facebookcoupons->save($fbcoupons);
				$shareCouponId = $result1->id;
				$message = "Thanks for sharing this Awesome Product You get $couponPercent % off for these exclusive products using promo code Use this One time promo code at the checkout";

				echo '{"status":"true","promo_code":"' . $generatevalue . '","message":"' . $message . '"}';
				die;

			} else {
				echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
				die;
			}
		} else {
			echo '{"status":"false","message":"Already used this offer"}';
			die;
		}

	}

	function get_uniquecode($seed_length = null)
	{
		//$seed = md5(srand((double)microtime()*1000000))."ABCDEFGHIJKLMNOPQRSTUVWXYZ234567892345678923456789";
		//$seed = md5(srand((double)microtime()*1000000))."ABCDEFGHIJKLMNOPQRSTUVWXYZ2345678923456789abcdefghijklmnopqrstuvwxyz23456789abcdefghijklmnopqrstuvwxyz";
		if (empty($seed_length)) {
			$seed_length = 8;
		}
		$seed = md5(srand((double)microtime() * 1000000) + (strtotime('now'))) . "ABCDEFGHIJKLMNOPQRSTUVWXYZ2345678923456789abcdefghijklmnopqrstuvwxyz23456789abcdefghijklmnopqrstuvwxyz";
		$str = '';
		srand((double)microtime() * 1000000);
		for ($i = 0; $i < $seed_length; $i++) {
			$str .= substr($seed, rand() % 48, 1);
		}
		return $str;
	}

	function help()
	{

		$this->loadModel('Users');
		$this->loadModel('Helps');
		$this->loadModel('Faqs');
		$this->loadModel('Sitesettings');
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->first();
		$admin_data = $this->Users->find()->where(['id' => 1])->first();
		$help_data = $this->Helps->find()->where(['id' => 1])->first();
		$faqData = $this->Faqs->find()->all();
		$contact_detail = json_decode($help_data['contact'], true);
		$email = $contact_detail['emailid'];
		$phone = $contact_detail['mobileno'];
		$address = urlencode($contact_detail['contactaddress']);
		$contact_topics = array('Forgot my password', 'Order Inquiry', 'Payment Issues', 'Returns and Refunds', '' . $setngs['site_name'] . ' Site Features', '' . $setngs['site_name'] . ' Mobile Features', 'Partnership Opportunities', 'Copyright Issue');
		$faqDetails = "";
		if (!empty($faqData)) {
			foreach ($faqData as $faq) {
				$faqDetails .= "</br><b>{$faq['faq_question']}</b></br>{$faq['faq_answer']}</br>";
			}
		}
		$main_termsofSale = $help_data['main_termsofSale'];
		$sub_termsofSale = $help_data['sub_termsofSale'];
		$main_termsofService = $help_data['main_termsofService'];
		$sub_termsofService = $help_data['sub_termsofService'];
		$main_privacy = $help_data['main_privacy'];
		$sub_privacy = $help_data['sub_privacy'];
		$main_termsofMerchant = $help_data['main_termsofMerchant'];
		$sub_termsofMerchant = $help_data['sub_termsofMerchant'];
		$main_copyright = $help_data['main_copyright'];
		$sub_copyright = $help_data['sub_copyright'];
		$resultarray = array();
		$resultarray[0]['page_name'] = "FAQ";
		$resultarray[0]['main_content'] = $faqDetails;//$main_faq;
		$resultarray[0]['sub_content'] = "";

		$resultarray[1]['page_name'] = "Terms of sale";
		$resultarray[1]['main_content'] = $main_termsofSale;
		$resultarray[1]['sub_content'] = $sub_termsofSale;


		$resultarray[2]['page_name'] = "Terms & service";
		$resultarray[2]['main_content'] = $main_termsofService;
		$resultarray[2]['sub_content'] = $sub_termsofService;


		$resultarray[3]['page_name'] = "Privacy Policy";
		$resultarray[3]['main_content'] = $main_privacy;
		$resultarray[3]['sub_content'] = $sub_privacy;


		$resultarray[4]['page_name'] = "Terms and Condition";
		$resultarray[4]['main_content'] = $main_termsofMerchant;
		$resultarray[4]['sub_content'] = $sub_termsofMerchant;


		$resultarray[5]['page_name'] = "Copyright Policy";
		$resultarray[5]['main_content'] = $main_copyright;
		$resultarray[5]['sub_content'] = $sub_copyright;

		echo '{"status": "true","email": "' . $email . '","phone": "' . $phone . '","address": "' . $address . '","contact_topics":' . json_encode($contact_topics) . ',"result": ' . json_encode($resultarray) . '}';
		die;

		if (empty($resultarray)) {
			echo '{"status": "false", "message": "No data found"}';
			die;

		}

	}

	function gethashtag()
	{

		$searchWord = $_POST['key' ];
		$this->loadModel('Hashtags');

		if ($searchWord != null) {
			$hashtagModel = $this->Hashtags->find()->where(['hashtag LIKE' => '%' . $searchWord . '%'])->order(['usedcount DESC'])->all();//all',array('conditions'=>array(
						//'hashtag like'=>$searchWord.'%'),'order'=>'usedcount DESC','limit'=>5));

			$hashContent = array();
			if (count($hashtagModel) != 0) {
				foreach ($hashtagModel as $hashtag) {
					$tagName = $hashtag['hashtag'];
					$hashContent[] = $tagName;
				}

				$resultArray = json_encode($hashContent);

				echo '{"status":"true","result":' . $resultArray . '}';
				die;
			} else {
				echo '{"status":"false","message":"No match found"}';
				die;
			}
		} else {
			echo '{"status":"false","message":"Search word is Empty"}';
			die;
		}
	}

	function inviteHistory()
	{

		$user_id = $_POST['user_id'];
		$this->loadModel('Users');
		$this->loadModel('Userinvitecredits');
		$this->loadModel('Forexrates');
		$this->loadModel('Sitesettings');
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();

		$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();

		$userDetail = $this->Users->find()->where(['id' => $user_id])->first();
		if (count($userDetail) == 0) {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		}
		$creditAmount = $setngs[0]['site_changes'];
		$creditAmount = json_decode($creditAmount, true);

		$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();
		if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "" || count($userDetail) == 0) {
			$cur_symbol = $forexrateModel['currency_symbol'];
			$cur = $forexrateModel['price'];
		} else {
			$cur_symbol = $currency_value['currency_symbol'];
			$cur = $currency_value['price'];
		}

		$siteurlsfor_ref = SITE_URL . 'signup?referrer=' . $userDetail['username_url'] . ''; //echo $siteurlsfor_ref;
		$url = $this->getUrlShorten($siteurlsfor_ref);

		$resultArray = array();
		$inviteuser = $this->Userinvitecredits->find()->where(['invited_friend' => $user_id])->order(['cdate DESC'])->all();
		$tot = 0;
		foreach ($inviteuser as $key => $inviteusers) {

			$resultArray[$key]['user_id'] = $inviteusers['invited_friend'];
			$inviteuserDetail = $this->Users->find()->where(['id' => $inviteusers['user_id']])->first();
			$resultArray[$key]['user_name'] = $inviteuserDetail['username'];
			$resultArray[$key]['created_date'] = $inviteusers['cdate'];

			$resultArray[$key]['credits'] = $this->Currency->conversion($forexrateModel['price'], $cur, $inviteusers['credit_amount']);

			$tot += $resultArray[$key]['credits'];
		}

		echo '{"status": "true", "currency": "' . $forexrateModel['currency_symbol'] . '","credits": "' . $userDetail['credit_total'] . '","credit_per_invite": "' . $creditAmount['credit_amount'] . '","referral_url": "' . $url . '", "result":' . json_encode($resultArray) . ' }';
		die;
	}

	function refundHistory()
	{
		$user_id = $_POST['user_id'];
		$orderstable = TableRegistry::get('Orders');
		$ordersModel = $orderstable->find('all')->where(['userid' => $user_id])->where(['status' => 'Refunded'])->where(['refunded_amount !=' => ''])->order(['refunded_date DESC'])->all();
		$ordersModelCount = $orderstable->find('all')->where(['userid' => $user_id])->where(['status' => 'Refunded'])->where(['refunded_amount !=' => ''])->order(['refunded_date DESC'])->count();

		if ($ordersModelCount > 0) {
			foreach ($ordersModel as $okey => $orderitem) {
				$resultArray[$okey]['order_id'] = $orderitem['orderid'];
				$resultArray[$okey]['refund_date'] = $orderitem['refunded_date'];
				/* Refund Currency */
				$shopCurrencyDetails = $this->Forexrates->find()->where(['currency_code' => 
					$orderitem['currency']])->first();
				/* Site Default Currency */
				$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();
				$refunded_amount=$this->Currency->conversion($shopCurrencyDetails['price'], $forexrateModel['price'], $orderitem['refunded_amount']);
				$resultArray[$okey]['credits'] = $refunded_amount;
			}
			echo '{"status": "true","result":' . json_encode($resultArray) . ' }';
			die;
		} else {
			echo '{"status": "false", "message": "No data found"}';
			die;
		}
	}


	function sharedHistory()
	{
		$user_id = $_POST['user_id'];
		
		$this->loadModel('Users');
		$this->loadModel('Shareproducts');
		$this->loadModel('Forexrates');

		$shareproductstable = TableRegistry::get('Shareproducts');
		$shareproducts =  $shareproductstable->find()->contain('Items')->where(['sender_id' => $user_id])->where(['Shareproducts.status' => 'paid'])->order(['Shareproducts.id DESC'])->all();

		$shareproductscount =  $shareproductstable->find()->contain('Items')->where(['sender_id' => $user_id])->where(['Shareproducts.status' => 'paid'])->order(['Shareproducts.id DESC'])->count();

		$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();

		$userDetail = $this->Users->find()->where(['id' => $user_id])->first();
		if (count($userDetail) == 0) {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		}
		
		$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();
		if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "" || count($userDetail) == 0) {
			$cur_symbol = $forexrateModel['currency_symbol'];
			$cur = $forexrateModel['price'];
		} else {
			$cur_symbol = $currency_value['currency_symbol'];
			$cur = $currency_value['price'];
		}

		$resultArray = array();
		// echo $shareproductscount;die;
		if ($shareproductscount > 0) {
			foreach ($shareproducts as $key => $sharepdt) {
				$resultArray[$key]['user_id'] = $sharepdt['receiver_id'];
				$shareuserDetail = $this->Users->find()->where(['id' =>  $sharepdt['receiver_id']])->first();
				$resultArray[$key]['user_name'] = $shareuserDetail['username'];
				$resultArray[$key]['created_date'] = $sharepdt['created_date'];
				$resultArray[$key]['credits'] = $this->Currency->conversion($forexrateModel['price'], $cur, $sharepdt['share_amount']);
			}
			echo '{"status": "true","result":' . json_encode($resultArray) . ' }';
			die;
		} else {
			echo '{"status": "false", "message": "No data found"}';
			die;
		}
	}

	function getUrlShorten($url = null)
	{
		$shorturl = '';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://api.bit.ly/shorten?version=2.0.1&login=helderh&longUrl=" . $url . "&apiKey=R_828015046ab107868e680095b2d56b1a");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		$res = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		if ($res === false || $info['http_code'] != 200) {
			$ret = array(false, $info['http_code'], $res);
		} else {
			$ret = array(true, $info['http_code'], $res);
		}

		if ($ret[1] == "200") {
			$results = json_decode($ret[2], true);
			//echo "<pre>";print_r($results);echo "</pre>";
			//$shorturl = trim($results['results'][$longurl]['shortUrl']);
			foreach ($results['results'] as $result) {
				$shorturl = $result['shortUrl'];
			}
		}
		return $shorturl;

	}

	function uploadSelfie()
	{

		$this->loadModel('Fashionusers');
		$userid = $_POST['user_id'];

		$imageName = $_POST['image'];
		$itemId = $_POST['item_id'];
		$img = explode("/", $imageName);
		$var = end($img);
			//$ext = strrchr($imageName, '.');
			//$image = time().rand(0, 9).$ext;
		$fashion_data = $this->Fashionusers->newEntity();
		$fashion_data->user_id = $userid;
		$fashion_data->userimage = $var;
		$fashion_data->itemId = $itemId;
		$fashion_data->cdate = time();

		if (!empty($userid) && !empty($itemId) && !empty($imageName)) {
			$this->Fashionusers->save($fashion_data);
			echo '{"status":"true","message":"Selfies created"}';
			die;
		} else {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		}
	}

	function braintreeclientToken()
	{

		include_once(WWW_ROOT . 'braintree/lib/Braintree.php');
		$this->loadModel('Sitesettings');
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->first();
		$paystatus = $setngs['braintree_setting'];
		$paystatus = json_decode($paystatus, true);
		$this->loadModel('Users');
		$this->loadModel('Forexrates');
		$user_id = $_POST['user_id'];
		$userDetail = $this->Users->find()->where(['id' => $user_id])->first();
		$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();

		$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();
		if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "" || count($userDetail) == 0) {
			$currency_code = $forexrateModel['currency_code'];

		} else {
			$currency_code = $currency_value['currency_code'];

		}

		foreach ($paystatus as $key => $value) {
			if ($key == $currency_code) {

				$merchant_account_id = $value['merchant_account_id'];

			}
		}
		$merchantid_settings = $setngs['merchantid_setting'];
		$merchantid_settings = json_decode($merchantid_settings, true);
		$params = array(
			"testmode" => $merchantid_settings['type'],
			"merchantid" => $merchantid_settings['merchant_id'],
			"publickey" => $merchantid_settings['public_key'],
			"privatekey" => $merchantid_settings['private_key'],
		);
		if ($params['testmode'] == "sandbox") {
			\Braintree_Configuration::environment('sandbox');
		} else {
			\Braintree_Configuration::environment('production');
		}

		\Braintree_Configuration::merchantId($params["merchantid"]);
		\Braintree_Configuration::publicKey($params["publickey"]);
		\Braintree_Configuration::privateKey($params["privatekey"]);

		if ($user_detls['customer_id'] == "") {
			$clientToken = \Braintree_ClientToken::generate([
				"merchantAccountId" => $merchant_account_id
			]);
		} else {
			$clientToken = Braintree_ClientToken::generate([
				"customerId" => $user_detls['customer_id'],
				"merchantAccountId" => $merchant_account_id
			]);
		}

		if ($clientToken && $clientToken != "") {
			echo '{"status":"true","token":"' . $clientToken . '"}';
			die;
		} else {
			echo '{"status":"true","message":"Token cannot be created now, Sorry!"}';
			die;
		}

	}

	function getCounts()
	{
		if ($this->sitemaintenance() == 0) {
			echo '{"status":"error","message":"Site under maintenance mode"}';
			die;
		}
		$user_id = $_POST['user_id'];
		if ($this->disableusercheck($user_id) == 0) {
			echo '{"status":"error","message":"The user has been blocked by admin"}';
			die;
		}
		$this->loadModel('Users');
		$this->loadModel('Carts');
		$this->loadModel('Items');
		$this->loadModel('Contactsellers');
		$this->loadModel('Forexrates');
		$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();
		$userDetail = $this->Users->find()->where(['id' => $user_id])->first();
		$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();
		if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "" || count($userDetail) == 0) {
			$cur_symbol = $forexrateModel['currency_symbol'];
			$cur_code = $forexrateModel['currency_code'];
		} else {
			$cur_symbol = $currency_value['currency_symbol'];
			$cur_code = $currency_value['currency_code'];
		}
		$user_data = $this->Users->find()->where(['id' => $user_id])->first();
		if (count($user_data) == 0) {
			echo '{"status": "false", "message": "Something went to be wrong,Please try again later."}';
			die;

		}
		$cartModel = $this->Carts->find()->where(['user_id' => $user_id])->andWhere(['payment_status' => 'progress'])->all();
		if (count($cartModel) != 0) {

			foreach ($cartModel as $carts) {
				$itemId[] = $carts['item_id'];
			}
			$cartCount = $this->Items->find()->where(['id IN' => $itemId])->andWhere(['status' => 'publish'])->count();
		}
		$notificationCount = $user_data['unread_notify_cnt'];
		$livefeedCount = $user_data['unread_livefeed_cnt'];
		$credits = $user_data['credit_total'];
		$messageCount = $this->Contactsellers->find()->where(['merchantid' => $user_id, ['sellerread' => 1]])->orWhere(['buyerid' => $user_id, ['buyerread' => 1]])->count();//'count',array('conditions'=>array(

		echo '{"status":"true","mesage_count":"' . $messageCount . '","cart_count":"' . $cartCount . '","notification_count":"' . $notificationCount . '","livefeed_count":"' . $livefeedCount . '","credits":"' . $credits . '","currency":"' . $cur_symbol . '","currency_code":"' . $cur_code . '"}';
		die;

	}

	/* GROUPGIFT PAYMENT */
	function payGroupgift()
	{
		if ($this->sitemaintenance() == 0) {
			echo '{"status":"error","message":"Site under maintenance mode"}';
			die;
		}

		include_once(WWW_ROOT . 'braintree/lib/Braintree.php');
		$this->loadModel('Sitesettings');
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->first();
		$paystatus = $setngs['braintree_setting'];
		$paystatus = json_decode($paystatus, true);
		$this->loadModel('Groupgiftpayamts');
		$this->loadModel('Groupgiftuserdetails');
		$this->loadModel('Items');
		$this->loadModel('Users');
		$this->loadModel('Countries');
		$this->loadModel('Orders');
		$this->loadModel('Order_items');
		$this->loadModel('Invoices');
		$this->loadModel('Invoiceorders');
		$this->loadModel('Shippingaddresses');
		$this->loadModel('Forexrates');

		$currentUserId = $_POST['user_id'];
		if ($this->disableusercheck($currentUserId) == 0) {
			echo '{"status":"error","message":"The user has been blocked by admin"}';
			die;
		}
		$itemIds = $_POST['item_id'];
		$ggId = $_POST['gift_id'];
		$amount = round($_POST['amount'], 2);
		$currency = $_POST['currency_code'];
		$nonce = $_POST['pay_nonce'];
		$totalprice = $amount;

		foreach ($paystatus as $key => $value) {
			if ($key == $currency) {

				$merchant_account_id = $value['merchant_account_id'];

			}
		}
		$merchantid_settings = $setngs['merchantid_setting'];
		$merchantid_settings = json_decode($merchantid_settings, true);
		$params = array(
			"testmode" => $merchantid_settings['type'],
			"merchantid" => $merchantid_settings['merchant_id'],
			"publickey" => $merchantid_settings['public_key'],
			"privatekey" => $merchantid_settings['private_key'],
		);

		if ($params['testmode'] == "sandbox") {
			\Braintree_Configuration::environment('sandbox');
		} else {
			\Braintree_Configuration::environment('production');
		}

		\Braintree_Configuration::merchantId($params["merchantid"]);
		\Braintree_Configuration::publicKey($params["publickey"]);
		\Braintree_Configuration::privateKey($params["privatekey"]);
		$user_detls = $this->Users->find()->where(['id' => $currentUserId])->first();

		if (empty($user_detls['customer_id'])) {

			$result1 = \Braintree_Customer::create([
				'firstName' => $user_detls['first_name'],
				'lastName' => $user_detls['last_name'],
				'paymentMethodNonce' => $nonce
			]);

			$customer_id = $result1->customer->id;
			$result = \Braintree_Transaction::sale(
				[
					'paymentMethodToken' => $result1->customer->paymentMethods[0]->token,
					'amount' => $totalprice,
					"merchantAccountId" => $merchant_account_id,
					'options' => [
						'submitForSettlement' => true
					]
				]
			);

		} else {
			$customer_id = $user_detls['customer_id'];

			$result = \Braintree_Transaction::sale([
				'amount' => $totalprice,
				'paymentMethodNonce' => $nonce,
				"merchantAccountId" => $merchant_account_id,
				'options' => [
					'submitForSettlement' => true
				]
			]);
		}

		if ($result->success == 1) {
			if (empty($user_detls['customer_id'])) {
				$this->Users->updateAll(array('customer_id' => $customer_id), array('id' => $currentUserId));
			}

			$ggitemDetails = $this->Groupgiftuserdetails->find()->where(['id' => $ggId])->first();//ById($ggId);

			/*CURRENCY CONVERSION */
			$groupgiftCurrency = $this->Forexrates->find()->where(['id' => $ggitemDetails['currencyid']])->first();
			$convertCurrency = $this->Forexrates->find()->where(['currency_code' => $currency])->first();
				//$amount = $this->Currency->conversion($groupgiftCurrency['price'],$convertCurrency['price'],$amount);
			$amount = $this->Currency->conversion($convertCurrency['price'], $groupgiftCurrency['price'], $amount);

			$groupgift_payamt = $this->Groupgiftpayamts->newEntity();
			$groupgift_payamt->ggid = $ggId;
			$groupgift_payamt->paiduser_id = $currentUserId;
			$groupgift_payamt->amount = $amount;
			$groupgift_payamt->cdate = time();
			$this->Groupgiftpayamts->save($groupgift_payamt);

			$balance_amt = $ggitemDetails['balance_amt'];
			$itemId = $ggitemDetails['item_id'];
			$ggcreateuserId = $ggitemDetails['user_id'];
			$name = $ggitemDetails['name'];
			$address1 = $ggitemDetails['address1'];
			$address2 = $ggitemDetails['address2'];
			$state = $ggitemDetails['state'];
			$city = $ggitemDetails['city'];
			$zipcode = $ggitemDetails['zipcode'];
			$telephone = $ggitemDetails['telephone'];
			$country = $ggitemDetails['country'];
			$itemcost = $ggitemDetails['itemcost'];
			$itemsize = $ggitemDetails['itemsize'];
			$itemquantity = $ggitemDetails['itemquantity'];
			$shipcost = $ggitemDetails['shipcost'];
			$tax = $ggitemDetails['tax'];
			if ($shipcost == '') {
				$shipcost = 0;
			} else {
				$shipcost = $shipcost;
			}

			$countryDetails = $this->Countries->find()->where(['id' => $country])->first();
			$countryName = $countryDetails['country'];

			$shipping_address = $this->Shippingaddresses->newEntity();
			$shipping_address->userid = $ggcreateuserId;
			$shipping_address->name = $name;
			$shipping_address->nickname = time();
			$shipping_address->country = $countryName;
			$shipping_address->state = $state;
			$shipping_address->address1 = $address1;
			$shipping_address->address2 = $address2;
			$shipping_address->city = $city;
			$shipping_address->zipcode = $zipcode;
			$shipping_address->phone = $telephone;
			$shipping_address->countrycode = $country;
			$shipping_addressresult = $this->Shippingaddresses->save($shipping_address);
			$shippingid = $shipping_addressresult->shippingid;

			$item_datas = $this->Items->find()->where(['id' => $itemId])->first();
			$userDatass = $this->Users->find()->where(['id' => $ggcreateuserId])->first();

			$shopEmailId = $item_datas['email'];
			$itemName = $item_datas['item_title'];

			$usernameforsupp = $item_datas['first_name'];

			$usernameforcust = $userDatass['first_name'];
			$CrntUserEmailId = $userDatass['email'];

			$tot_quantity = $itemquantity;
			$tot_size = $itemsize;

			$balance_amt = round(($balance_amt - $amount), 2);
			$balance_amt = $balance_amt < 1 ? 0 : $balance_amt;
			$this->Groupgiftuserdetails->updateAll(array('balance_amt' => $balance_amt), array('id' => $ggId));

			if ($balance_amt <= 0) {
				$this->Groupgiftuserdetails->updateAll(array('status' => "Completed"), array('id' => $ggId));
				$order_data = $this->Orders->newEntity();
				$order_data->userid = $ggcreateuserId;
				$order_data->totalcost = $itemcost;
				$order_data->orderdate = time();
				$order_data->shippingaddress = $shippingid;
				$order_data->coupon_id = '0';
				$order_data->discount_amount = '0';
				$order_data->totalCostshipp = $shipcost;
				$order_data->currency = $groupgiftCurrency['currency_code'];
				$order_data->status = "Pending";
				$order_data->tax = $tax;
				$order_dataresult = $this->Orders->save($order_data);
				/*  ORDER ITEMS */
				$orderId = $order_dataresult->orderid;
				$orderitem_data = $this->Order_items->newEntity();
				$orderitem_data->orderid = $orderId;
				$orderitem_data->itemid = $itemId;
				$orderitem_data->itemname = $itemName;
				$orderitem_data->itemprice = $itemcost;
				$orderitem_data->itemquantity = $itemquantity;
				$orderitem_data->itemunitprice = $itemcost / $itemquantity;
				$orderitem_data->shippingprice = $shipcost;
				$orderitem_data->item_size = $itemsize;
				$orderitem_data->tax = $tax;
				$orderitem_dataresult = $this->Order_items->save($orderitem_data);

				$itemModel = $this->Items->find()->where(['id' => $itemId])->first();
				$quantityItem = $itemModel['quantity'];
				$user_id = $itemModel['user_id'];
				$itemopt = $itemModel['size_options'];

				if (!empty($itemopt)) {
					if ($itemsize != "") {
						$seltsize = $itemsize;
						$sizeqty = $itemopt;
						$sizeQty = json_decode($sizeqty, true);
						$sizeQty['unit'][$seltsize] = $sizeQty['unit'][$seltsize] - $itemquantity;
					}
				}
				if ($cartSize != "") {
					$itemModel->size_options = json_encode($sizeQty);
				} else {
					$itemModel->size_options = '';
				}

				$this->Orders->updateAll(array('merchant_id' => $user_id), array('orderid' => $orderId));
				$invoiceId = $this->Invoices->find()->order(['invoiceid DESC'])->first();
				$invoiceId = $invoiceId['invoiceid'] + 1;
				$inv_data = $this->Invoices->newEntity();
				$inv_data->invoiceno = 'INV' . $invoiceId . $ggcreateuserId;
				$inv_data->invoicedate = time();
				$inv_data->invoicestatus = 'Completed';
				$inv_data->paymentmethod = $result->transaction->paymentInstrumentType;
				$inv_dataresult = $this->Invoices->save($inv_data);
				$invoiceId = $inv_dataresult->invoiceid;

				$invorder_data = $this->Invoiceorders->newEntity();
				$invorder_data->invoiceid = $invoiceId;
				$invorder_data->orderid = $orderId;
				$this->Invoiceorders->save($invorder_data);

				/* * Update the Affiliate Product Share commission save to Sharing person * */
            
            $shareproducts = TableRegistry::get('Shareproducts')->find()->where(['receiver_id' => $ggcreateuserId])->where(['status' => 'visit'])->all();
            if(!empty($shareproducts)){
                foreach($shareproducts as $sharepdt) {
                    $sharepdtid = $sharepdt['item_id'];
                    //if(in_array($sharepdtid, $itemmailids)) {

                 $itemModel = TableRegistry::get('Items')->find()->where(['id' => $sharepdtid])->first();
                 
                 $orderitemModel = TableRegistry::get('Order_items')->find('all')->where(['orderid' => $orderId])->first();
                 // $ordersize = $orderitemModel->item_size;
                 //    if ($ordersize != "" && $ordersize != 0) {
                 //         $product_store = json_decode($itemModel['size_options'], true);

                         
                 //     if(in_array($ordersize,$product_store['size']))
                 //     {
                 //          $itemprice = $product_store['price'][$ordersize];
                           
                 //     }
                 //    } else {
                 //        $itemprice = $itemModel->price;
                 //    }

                    $itemprice = $orderitemModel->itemprice; //quantity based price
                    $affiliatecommission = $itemModel->affiliate_commission;
                    $commission_amount =  $itemprice * $affiliatecommission / 100;

                    $sharedquery = TableRegistry::get('Shareproducts')->query();
                    $shareduserquery = TableRegistry::get('Users')->query();
                    $sharedquery->update()->set(['order_id' => $orderId, 'status' => 'purchased', 'share_amount' => $commission_amount])->where(['receiver_id' => $ggcreateuserId])->where(['item_id' => $sharepdtid])->where(['status' => 'visit'])->execute();

                   // }
                }
            }
           
            /* * End Update the Affiliate Product Share commission save to Sharing person * */

            /* GROUPGIFTCARD EMAILS */
        $Sitesettings = TableRegistry::get('Sitesettings')->find('all')->first();
        $subject = __d('user', 'Group Gift Notification');
        $template = 'ggcust';
        $messages = "";
        $emailidcust = base64_encode($CrntUserEmailId);
        $orderIdcust = base64_encode($orderId);
        $setdata = array('sitelogo' => $Sitesettings['site_logo'], 'sitename' => $Sitesettings['site_name'], 'custom' => $usernameforcust, 'loguser' => $loguser, 'itemname' => $itemName, 'tot_quantity' => $tot_quantity, 'tot_size' => $tot_size, 'access_url' => $_SESSION['site_url'] . "custupdate/" . $emailidcust . "~" . $orderIdcust, 'access_url_n_d' => $_SESSION['site_url'] . "custupdatend/" . $emailidcust . "~" . $orderIdcust);
        $this->sendmail($CrntUserEmailId, $subject, $messages, $template, $setdata);

        $userinfo = TableRegistry::get('Users')->find('all')->where(['email' => $CrntUserEmailId])->first();
        $this->loadModel('Userdevices');
        $userddett = $this->Userdevices->find('all', array('conditions' => array('user_id' => $userinfo['id'])));
        $userdevicestable = TableRegistry::get('Userdevices');
        $userddett = $userdevicestable->find('all')->where(['user_id' => $userinfo['id']])->all();
        foreach ($userddett as $userdet) {
            $deviceTToken = $userdet['deviceToken'];
            $badge = $userdet['badge'];
            $badge +=1;
            $querys = $userdevicestable->query();
            $querys->update()
            ->set(['badge' => $badge])
            ->where(['deviceToken' => $deviceTToken])
            ->execute();
            if (isset($deviceTToken)) {
                $userprofileimage = $userinfo['profile_image'];
                if ($userprofileimage == "")
                    $userprofileimage = "usrimg.jpg";
                $pushMessage['type'] = "groupgift";
                $pushMessage['user_id'] = $userinfo['id'];
                $pushMessage['user_name'] = $userinfo['username'];
                $pushMessage['user_image'] = $userprofileimage;
                $pushMessage['gift_id'] = $ggId;
                $user_detail = TableRegistry::get('Users')->find()->where(['id' => $userinfo['id']])->first();
                I18n::locale($user_detail['languagecode']);
                $pushMessage['message'] = __d('user', "You have Created the item for Group gift. Soon your friend  will get the Item from") . ' ' . $Sitesettings['site_name'];
                $messages = json_encode($pushMessage);
                $this->pushnot($deviceTToken, $messages, $badge);
            }
        }

        $userDET = TableRegistry::get('Groupgiftpayamts')->find()->where(['ggid' => $ggId])->all();
        foreach ($userDET as $userss) {
            $userdetails = TableRegistry::get('Users')->find('all')->where(['id' => $userss->paiduser_id])->first();
            $emailss[] = $userdetails['email'];
            $usernamess[] = $userdetails['username'];
            $userids[] = $userdetails['id'];
            if ($userdetails['profile_image'] == "")
                $userimage[] = "usrimg.jpg";
            else
                $userimage[] = $userdetails['profile_image'];
        }
        foreach ($emailss as $keyy => $emailss1) {
            $subject = __d('user', 'Group Gift Notification');
            $template = 'ggcontribute';
            $messages = "";
            $emailidcust = base64_encode($CrntUserEmailId);
            $orderIdcust = base64_encode($orderId);
            $setdata = array('custom' => $usernamess[$keyy], 'loguser' => $loguser, 'itemname' => $itemName, 'tot_quantity' => $tot_quantity, 'tot_size' => $tot_size, 'access_url' => $_SESSION['site_url'] . "custupdate/" . $emailidcust . "~" . $orderIdcust, 'access_url_n_d' => $_SESSION['site_url'] . "custupdatend/" . $emailidcust . "~" . $orderIdcust);
            $this->sendmail($emailss1, $subject, $messages, $template, $setdata);

            $userinfo = TableRegistry::get('Users')->find('all')->where(['email' => $emailss1])->first();
            /* GIFTCARD PUSH NOTIFICATIONS */
            $this->loadModel('Userdevices');
            $userddett = $this->Userdevices->find('all', array('conditions' => array('user_id' => $userinfo['id'])));
            $userdevicestable = TableRegistry::get('Userdevices');
            $userddett = $userdevicestable->find('all')->where(['user_id' => $userinfo['id']])->all();
            foreach ($userddett as $userdet) {
                $deviceTToken = $userdet['deviceToken'];
                $badge = $userdet['badge'];
                $badge +=1;
                $querys = $userdevicestable->query();
                $querys->update()
                ->set(['badge' => $badge])
                ->where(['deviceToken' => $deviceTToken])
                ->execute();
                if (isset($deviceTToken)) {

                    $pushMessage['type'] = "groupgift";
                    $pushMessage['user_id'] = $userids[$keyy];
                    $pushMessage['user_name'] = $usernamess[$keyy];
                    $pushMessage['user_image'] = $userimage[$keyy];
                    $pushMessage['gift_id'] = $ggId;
                    $user_detail = TableRegistry::get('Users')->find()->where(['id' => $userinfo['id']])->first();
                    I18n::locale($user_detail['languagecode']);
                    $pushMessage['message'] = $usernamess[$keyy] . " " . __d('user', 'Contributed the product for Group gift . Soon your friend  will get the Product from') . " " . $Sitesettings['site_name'];
                    $messages = json_encode($pushMessage);
                    $this->pushnot($deviceTToken, $messages, $badge);
                }
            }
        }

        $subject = __d('user', 'Order notification');
        $template = 'ggsupp';
        $messages = "";
        $emailidsell = base64_encode($shopEmailId);
        $orderIdmer = base64_encode($orderId);
        $setdata = array('custom' => $usernameforsupp, 'loguser' => $loguser, 'itemname' => $itemName, 'tot_quantity' => $tot_quantity, 'tot_size' => $tot_size, 'access_url' => $_SESSION['site_url'] . "custupdate/" . $emailidcust . "~" . $orderIdcust, 'access_url_n_d' => $_SESSION['site_url'] . "custupdatend/" . $emailidcust . "~" . $orderIdcust, 'name' => $name, 'address1' => $address1, 'address2' => $address2, 'state' => $state, 'city' => $city, 'countryName' => $countryName, 'telephone' => $telephone, 'access_url' => $_SESSION['site_url'] . "merupdate/" . $emailidsell . "~" . $orderIdmer);


        $userinfo = TableRegistry::get('Users')->find('all')->where(['email' => $shopEmailId])->first();
        $this->loadModel('Userdevices');
        $userddett = $this->Userdevices->find('all', array('conditions' => array('user_id' => $userinfo['id'])));
        foreach ($userddett as $userdet) {
            $deviceTToken = $userdet['Userdevices']['deviceToken'];
            $badge = $userdet['Userdevices']['badge'];
            $badge +=1;
            $this->Userdevices->updateAll(array('badge' => $badge), array('deviceToken' => $deviceTToken));
            if (isset($deviceTToken)) {
                $pushMessage['message'] = "There is an order placed on you shop at" . $Sitesettings['site_name'];
                $messages = json_encode($pushMessage);
            }
        }

			}

			echo '{"status":"true","message":"Payment successful"}';
			die;

		} else {

			echo '{"status":"false","message":"Payment not successful"}';
			die;

		}

	}

	/* SENT GIFTCARD */
	function sentGiftcard()
	{
		$user_id = $_POST['user_id'];
		$this->loadModel('Giftcards');
		$this->loadModel('Users');
		$this->loadModel('forexrates');
		if (isset($_POST['limit'])) {
			$limit = $_POST['limit'];
		} else {
			$limit = 10;
		}
		if (isset($_POST['offset'])) {
			$giftcard_detail = $this->Giftcards->find('all', array(
				'conditions' => array(
					'user_id' => $user_id,
					'status' => 'Paid'
				),
				'limit' => $limit,
				'offset' => $_POST['offset'],
				'order' => 'id DESC',
			));

		} else {
			$giftcard_detail = $this->Giftcards->find('all', array(
				'conditions' => array(
					'user_id' => $user_id,
					'status' => 'Paid'
				),
				'limit' => $limit,
				'order' => 'id DESC',
			));
		}

		$resultarray = array();
		$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();
		$userDetail = $this->Users->find()->where(['id' => $user_id])->first();
		foreach ($giftcard_detail as $key => $giftcard_details) {
			$reciptentmailId = $giftcard_details['reciptent_email'];
			$reciptent_details = $this->Users->find()->where(['email' => $reciptentmailId])->first();
			$resultarray[$key]['recipient_id'] = $reciptent_details['id'];
			$resultarray[$key]['recipient_name'] = $reciptent_details['first_name'] . ' ' . $reciptent_details['last_name'];
			$resultarray[$key]['created_date'] = $giftcard_details['cdate'];
			$giftcard_value = $this->Forexrates->find()->where(['id' => $giftcard_details['currencyid']])->first();
			$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();
			if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "" || count($userDetail) == 0) {
				$currency_symbol = $forexrateModel['currency_symbol'];
			} else {
				$currency_symbol = $currency_value['currency_symbol'];
			}
			$resultarray[$key]['currency'] = $currency_symbol;

			$price = $this->Currency->conversion($giftcard_value['price'], 
				$currency_value['price'], $giftcard_details['amount']);
			$resultarray[$key]['gift_amount'] = $price;
		}

		if ($reciptentmailId == "") {
			echo '{"status": "false", "message": "No data found"}';
			die;

		} else {
			echo '{"status":"true","result":' . json_encode($resultarray) . '}';
			die;
		}

	}

	/* RECEIVE GIFTCARD */
	function receivedGiftcard()
	{

		$user_id = $_POST['user_id'];
		$this->loadModel('Giftcards');
		$this->loadModel('Users');
		$this->loadModel('forexrates');
		$userDetail = $this->Users->find()->where(['id' => $user_id])->first();
		$reciptentmailId = $userDetail['email'];
		$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();
		if (isset($_POST['limit'])) {
			$limit = $_POST['limit'];
		} else {
			$limit = 10;
		}
		if (isset($_POST['offset'])) {
			$giftcard_detail = $this->Giftcards->find('all', array(
				'conditions' => array(
					'reciptent_email' => $reciptentmailId,
					'status' => 'Paid'
				),
				'limit' => $limit,
				'offset' => $_POST['offset'],
				'order' => 'id DESC',
			));

		} else {
			$giftcard_detail = $this->Giftcards->find('all', array(
				'conditions' => array(
					'reciptent_email' => $reciptentmailId,
					'status' => 'Paid'
				),
				'limit' => $limit,
				'order' => 'id DESC',
			));
		}

		$resultarray = array();
		$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();
		$userDetail = $this->Users->find()->where(['id' => $user_id])->first();
		foreach ($giftcard_detail as $key => $giftcard_details) {
			$used_amount = $giftcard_details['amount'] - $giftcard_details['avail_amount'];
			$senderId = $giftcard_details['user_id'];
			$sender_details = $this->Users->find()->where(['id' => $senderId])->first();
			$resultarray[$key]['user_id'] = $giftcard_details['user_id'];
			$resultarray[$key]['user_name'] = $sender_details['first_name'] . ' ' . $reciptent_details['last_name'];
			$resultarray[$key]['created_date'] = $giftcard_details['cdate'];
			$resultarray[$key]['message'] = $giftcard_details['message'];
			
			$giftcard_value = $this->Forexrates->find()->where(['id' => $giftcard_details['currencyid']])->first();
			$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();
			if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "" || count($userDetail) == 0) {
				$currency_symbol = $forexrateModel['currency_symbol'];
			} else {
				$currency_symbol = $currency_value['currency_symbol'];
			}
			$resultarray[$key]['currency'] = $currency_symbol;
			$price = $this->Currency->conversion($giftcard_value['price'], 
				$currency_value['price'], $giftcard_details['amount']);
			$used_amount = $this->Currency->conversion($giftcard_value['price'], 
				$currency_value['price'], $used_amount);
			$resultarray[$key]['gift_amount'] = $price;
			$resultarray[$key]['used_amount'] = $used_amount;
			$resultarray[$key]['voucher_code'] = $giftcard_details['giftcard_key'];
		}
		if ($senderId == "") {
			echo '{"status": "false", "message": "No data found"}';
			die;

		} else {
			echo '{"status":"true","result":' . json_encode($resultarray) . '}';
			die;
		}

	}

	/* CREATE GIFTCARD */
	function createGiftcard()
	{
		$currentUserId = $_POST['user_id'];
		$amount = $_POST['price'];
		$recipientId = $_POST['recipient_id'];
		$nonce = $_POST['pay_nonce'];
		$message = $_POST['message'];
		$totalprice = $amount;
		$this->loadModel('Giftcards');
		$this->loadModel('Users');
		$this->loadModel('Forexrates');
		if ($currentUserId == $recipientId) {
			echo '{"status": "false", "message": "Gift card can not be send to your own"}';

		}
		include_once(WWW_ROOT . 'braintree/lib/Braintree.php');
		$this->loadModel('Sitesettings');
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->first();
		$paystatus = $setngs['braintree_setting'];
		$paystatus = json_decode($paystatus, true);
		$userDetail = $this->Users->find()->where(['id' => $currentUserId])->first();
		$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();

		$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();
		if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "" || count($userDetail) == 0) {
			$currency_code = $forexrateModel['currency_code'];

		} else {
			$currency_code = $currency_value['currency_code'];

		}

		foreach ($paystatus as $key => $value) {
			if ($key == $currency_code) {

				$merchant_account_id = $value['merchant_account_id'];

			}
		}
		$merchantid_settings = $setngs['merchantid_setting'];
		$merchantid_settings = json_decode($merchantid_settings, true);
		$params = array(
			"testmode" => $merchantid_settings['type'],
			"merchantid" => $merchantid_settings['merchant_id'],
			"publickey" => $merchantid_settings['public_key'],
			"privatekey" => $merchantid_settings['private_key'],
		);
		if ($params['testmode'] == "sandbox") {
			\Braintree_Configuration::environment('sandbox');
		} else {
			\Braintree_Configuration::environment('production');
		}

		\Braintree_Configuration::merchantId($params["merchantid"]);
		\Braintree_Configuration::publicKey($params["publickey"]);
		\Braintree_Configuration::privateKey($params["privatekey"]);

		$user_detls = $this->Users->find()->where(['id' => $currentUserId])->first();
		$recipient_detls = $this->Users->find()->where(['id' => $recipientId])->first();

		if (empty($user_detls['customer_id'])) {

			$result1 = \Braintree_Customer::create([
				'firstName' => $user_detls['first_name'],
				'lastName' => $user_detls['last_name'],
				'paymentMethodNonce' => $nonce
			]);

			$customer_id = $result1->customer->id;

			$result = \Braintree_Transaction::sale(
				[
					'paymentMethodToken' => $result1->customer->paymentMethods[0]->token,
					'amount' => $totalprice,
					"merchantAccountId" => $merchant_account_id,
					'options' => [
						'submitForSettlement' => true
					]
				]
			);

		} else {
			$customer_id = $user_detls['customer_id'];

			$result = \Braintree_Transaction::sale([
				'amount' => $totalprice,
				'paymentMethodNonce' => $nonce,
				"merchantAccountId" => $merchant_account_id,
				'options' => [
					'submitForSettlement' => true
				]
			]);
		}
		if ($result->success == 1) {

			if (empty($user_detls['customer_id'])) {
				$this->Users->updateAll(array('customer_id' => $customer_id), array('id' => $currentUserId));
			}
			$giftcards = $this->Giftcards->newEntity();
			$giftcards->user_id = $currentUserId;
			$giftcards->reciptent_name = $recipient_detls['first_name'] . ' ' . $recipient_detls['last_name'];
			$giftcards->reciptent_email = $recipient_detls['email'];
			$giftcards->message = $message;
			$giftcards->amount = $totalprice;
			$giftcards->avail_amount = $totalprice;
			$giftcards->giftcard_key = $this->Urlfriendly->get_uniquecode(8);
			$uniquecode = $this->Urlfriendly->get_uniquecode(8);
			$giftcards->status = 'Paid';
			$giftcards->cdate = time();
			$giftcards->currencyid = $user_detls['currencyid'];
			$this->Giftcards->save($giftcards);

			/* GIFTCARD LOGS */
			$image['user']['image'] = 'usrimg.jpg';
			$image['user']['link'] = '';
			$loguserimage = json_encode($image);
			$userids = $recipient_detls['id'];
			$notifymsg = "You have received a gift card -___-" . $uniquecode;
			$messages = "You have received a Gift card from your friend " . $user_detls['first_name'] . " worth " . $gcamount . " use this code on checkout: " . $uniquecode;
			$logdetails = $this->addlog('credit', 0, $userids, 0, $notifymsg, $messages, $loguserimage);

			/* GIFTCARD PUSH NOTIFICATIONS*/
			$this->loadModel('Userdevices');
			$userddett = $this->Userdevices->find('all', array('conditions' => array('user_id' => $recipientId)));
			$userdevicestable = TableRegistry::get('Userdevices');
			$userddett = $userdevicestable->find()->where(['user_id' => $recipientId])->all();
			foreach ($userddett as $userdet) {
				$deviceTToken = $userdet['deviceToken'];
				$badge = $userdet['badge'];
				$badge += 1;

				$querys = $userdevicestable->query();
				$querys->update()
				->set(['badge' => $badge])
				->where(['deviceToken' => $deviceTToken])
				->execute();

				if (isset($deviceTToken)) {
					$user_profile_image = $user_detls['profile_image'];
					if ($user_profile_image == "")
						$user_profile_image = "usrimg.jpg";
					$pushMessage['type'] = 'gift_card';
					$pushMessage['user_id'] = $user_detls['id'];
					$pushMessage['user_name'] = $user_detls['username'];
					$pushMessage['user_image'] = $user_profile_image;
					$user_detail = TableRegistry::get('Users')->find()->where(['id' => $recipientId])->first();
					I18n::locale($user_detail['languagecode']);
					$pushMessage['message'] = __d('user', "You have received a Gift card from your friend") . " " . $user_detls['first_name'];
					$messages = json_encode($pushMessage);
					$this->pushnot($deviceTToken, $messages, $badge);
				}
			}

			echo '{"status":"true","message":"Payment successful"}';
			die;
		} else {
			echo '{"status":"false","message":"Payment not successful"}';
			die;
		}
	}

	function adminDatas()
	{

		$this->loadModel('Countries');
		$this->loadModel('Sitequeries');
		$this->loadModel('Sitesettings');
		$this->loadModel('Forexrates');
		$this->loadModel('Languages');

		$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();

		$languageModel = $this->Languages->find()->where(['countrycode' => $forexrateModel['currency_code']])->first();
		$countryModel = $this->Countries->find()->where(['id' => $languageModel['countryid']])->first();
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
		$giftcard = $setngs[0]['giftcard'];
		$giftcard = json_decode($giftcard, true);
		$giftamount = $giftcard['amounts'];

		$resultArray1 = array();
		$sitequeriesModel = $this->Sitequeries->find()->where(['type' => 'Dispute_Problem'])->first();
		$resultArray1['site_logo'] = SITE_URL . 'images/logo/' . $setngs[0]['site_logo_icon'];
		$resultArray1['dispute_queries'] = array();
		$csqueries = json_decode($sitequeriesModel['queries'], true);
		if (!empty($csqueries)) {
			for ($s = 0; $s < count($csqueries); $s++) {
				$resultArray1['dispute_queries'][$s]['subject'] = $csqueries[$s];
			}
		} else {
			$resultArray1['dispute_queries'][0]['subject'] = "";
		}

		$resultArray1['country'] = array();
		$resultArray1['country'][0]['id'] = $countryModel['id'];
		$resultArray1['country'][0]['name'] = $countryModel['country'];

		$resultArray1['gift_amount'] = array();
		if ($giftamount != "") {
			$giftamount = explode(",", $giftamount);

			foreach ($giftamount as $key => $giftamounts) {
				$resultArray1['gift_amount'][$key]['value'] = $giftamounts;

			}
		}
		$resultarray1 = json_encode($resultArray1);
		if (empty($resultarray1)) {
			echo '{"status":"false","message":"No Data Found"}';
			die;

		}

		echo '{"status":"true","result":' . $resultarray1 . '}';
		die;
	}
	function itemdet()
	{

		$this->loadModel('Itemfavs');
		$this->loadModel('Carts');
		$this->loadModel('Items');
		$this->loadModel('Users');
		$this->loadModel('Tempaddresses');
		$this->loadModel('Shipings');
		$this->loadModel('Photos');
		$this->loadModel('Shops');
		$this->loadModel('Taxes');
		$this->loadModel('Forexrates');
		$size = $_POST['size'];
		$buy_quantity = $_POST['quantity'];
		$total_cost = 0;
		$ship_cost = 0;
		$today = strtotime(date("Y-m-d"));
		$user_data = $this->Users->find()->where(['id' => $_POST['user_id']])->first();
		$setngs = $this->Sitesettings->find()->toArray();
		$currency_value = $this->Forexrates->find()->where(['id' => $user_data['currencyid']])->first();
		if ($currency_value['currency_code'] == "USD" || $currency_value['currency_code'] == "") {
			$cur = 1;
			$cur_symbol = "$";
		} else {

			$cur = $currency_value['price'];
			$cur_symbol = $currency_value['currency_symbol'];
		}

		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}

		if ($_POST['shipping_id'] == 0) {
			$defaultAddress = $user_data['defaultshipping'];
		} else {
			$defaultAddress = $_POST['shipping_id'];
		}
		$shipping_address = $this->Tempaddresses->find()->where(['shippingid' => $defaultAddress])->first();

		if (SITE_URL == $_SESSION['media_url']) {
			$img_path = $_SESSION['media_url'];
		} else {
			$img_path = $_SESSION['media_url'];
		}
					//$listitem = TableRegistry::get('Items')->find()->contain('Forexrates')->where(['Items.id'=>$item_id])->first();

		$item_datas = TableRegistry::get('Items')->find()->contain('Forexrates')->where(['Items.id' => $_POST['item_id']])->first();
		if (count($item_datas) == 0) {
			echo '{"status":"false","message":"No Data Found"}';
			die;
		}

		$resultarray1 = array();
		$process_time = $item_datas['processing_time'];
		if ($process_time == '1d') {
			$process_time = "One business day";
		} elseif ($process_time == '2d') {
			$process_time = "Two business days";
		} elseif ($process_time == '3d') {
			$process_time = "Three business days";
		} elseif ($process_time == '4d') {
			$process_time = "Four business days";
		} elseif ($process_time == '2ww') {
			$process_time = "One-Two weeks";
		} elseif ($process_time == '3w') {
			$process_time = "Two-Three weeks";
		} elseif ($process_time == '4w') {
			$process_time = "Three-Four weeks";
		} elseif ($process_time == '6w') {
			$process_time = "Four-Six weeks";
		} elseif ($process_time == '8w') {
			$process_time = "Six-Eight weeks";
		}
		$resultarray['items'] = array();
		$resultarray['items'][0]['cart_id'] = "";
		$resultarray['items'][0]['item_id'] = $item_datas['id'];
		$resultarray['items'][0]['item_name'] = $item_datas['item_title'];
		$photo = $this->Photos->find()->where(['item_id' => $item_datas['id']])->first();
		if ($photo['image_name'] == "") {
			$itemImage = "usrimg.jpg";
		} else {
			$itemImage = $photo['image_name'];
		}
		$resultarray['items'][0]['item_image'] = $img_path . 'media/items/thumb350/' . $itemImage;
					//if($size!=""){

		$tdy = strtotime(date("Y-m-d"));

		if ($size != "") {

			$sizeoptions = $item_datas['size_options'];
			$sizes = json_decode($sizeoptions, true);
			if (!empty($sizes)) {
				$sizeoptions = $item_datas['size_options'];
				$sizes = json_decode($sizeoptions, true);
				$price = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $sizes['price'][$size]);
				$resultarray['items'][0]['mainprice'] = $price;
				if ($item_datas['discount_type'] == 'daily' && strtotime($item_datas['dealdate']) == $tdy) {
						$dailydealdiscount = $item_datas['discount'];
						$unitPriceConvert = number_format((float)$price, 2, '.', '');
						$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);
						if ($daily_price != "") {
							$pricetot += number_format((float)$daily_price, 2, '.', '');
						}
						$resultarray['items'][0]['price'] = number_format((float)$daily_price, 2, '.', '');
					} elseif($item_datas['discount_type'] == 'regular') {
					$dailydealdiscount = $item_datas['discount'];
					$unitPriceConvert = number_format((float)$price, 2, '.', '');
					$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);
					if ($daily_price != "") {
						$pricetot += number_format((float)$daily_price, 2, '.', '');
					}
					$resultarray['items'][0]['price'] = number_format((float)$daily_price, 2, '.', '');
					}else{
						$resultarray['items'][0]['price'] = $price;
					}

				$quantity = $sizes['unit'][$size];
			}
			$resultarray['items'][0]['size'] = $size;
		} else {
			$price = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $item_datas['price']);
			$resultarray['items'][0]['mainprice'] = $price;


			if ($item_datas['discount_type'] == 'daily' && strtotime($item_datas['dealdate']) == $tdy) {
				$dailydealdiscount = $item_datas['discount'];
				$unitPriceConvert = number_format((float)$price, 2, '.', '');
				$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);
				if ($daily_price != "") {
					$pricetot += number_format((float)$daily_price, 2, '.', '');
				}
						$resultarray['items'][0]['price'] = number_format((float)$daily_price, 2, '.', '');;
				} elseif($item_datas['discount_type'] == 'regular') {
					$dailydealdiscount = $item_datas['discount'];
					$unitPriceConvert = number_format((float)$price, 2, '.', '');
					$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);

					if ($daily_price != "") {
						$pricetot += number_format((float)$daily_price, 2, '.', '');
					}
					$resultarray['items'][0]['price'] = number_format((float)$daily_price, 2, '.', '');
			}else{
				$resultarray['items'][0]['price'] = $price;
			}

			$resultarray['items'][0]['size'] = "";
			$quantity = $item_datas['quantity'];
		}
		$resultarray['items'][0]['quantity'] = $buy_quantity;
		$resultarray['items'][0]['total_quantity'] = $quantity;

		$resultarray['items'][0]['shipping_time'] = $process_time;

		if (strtotime($item_datas['dealdate']) == $tdy && $item_datas['discount_type'] == 'daily') {
				$resultarray['items'][0]['deal_enabled'] = 'yes';
				$resultarray['items'][0]['pro_discount'] = 'dailydeal';
				$resultarray['items'][0]['discount_percentage'] = $item_datas['discount'];
			} elseif($item_datas['discount_type'] == 'regular') {
				$resultarray['items'][0]['deal_enabled'] = 'yes';
				$resultarray['items'][0]['pro_discount'] = 'regulardeal';
				$resultarray['items'][0]['discount_percentage'] = $item_datas['discount'];
			}else{
				$resultarray['items'][0]['deal_enabled'] = 'no';
				$resultarray['items'][0]['discount_percentage'] = 0;
			}


		/*
		if ($item_datas['dailydeal'] == "yes" && strtotime($item_datas['dealdate']) == $today) {
			$resultarray['items'][0]['deal_enabled'] = "yes";
			$resultarray['items'][0]['discount_percentage'] = $item_datas['discount'];
		} else {
			$resultarray['items'][0]['deal_enabled'] = "no";
			$resultarray['items'][0]['discount_percentage'] = 0;
		}
		*/
		$likedcount = $this->Itemfavs->find()->where(['item_id' => $item_datas['id']])->count();
		$resultarray['items'][0]['like_count'] = $likedcount;
		$resultarray['items'][0]['fbshare_discount'] = $item_datas['share_discountAmount'];
		$shop_data = $this->Shops->find()->where(['id' => $item_datas['shop_id']])->first();
		$shopCurrencyDetails = $this->Forexrates->find()->where(['currency_code' => $shop_data['currency']])->first();
		$freeamt = $this->Currency->conversion($shopCurrencyDetails['price'], $cur, $shop_data['freeamt']);

		$resultarray['items'][0]['store_id'] = $shop_data['id'];
		$resultarray['items'][0]['store_name'] = $shop_data['shop_name'];

		//echo '<pre>'; print_r($resultarray['items']); die;

		$total_cost += $resultarray['items'][0]['quantity'] * $resultarray['items'][0]['price'];
		$resultarray1['item_total'] = $total_cost;
		$shiping = $this->Shipings->find()->where(['item_id' => $item_datas['id']])->andWhere(['country_id' => $shipping_address['countrycode']])->first();
		if (count($shiping) == 0) {
			$shiping = $this->Shipings->find()->where(['item_id' => $item_datas['id']])->andWhere(['country_id' => 0])->first();

		}
		$postalcode = json_decode($shop_data['postalcodes'], true);
		if (in_array($shipping_address['zipcode'], $postalcode)) {
			$shipingprice = 0;
		} elseif ($total_cost >= $freeamt && $shop_data['pricefree'] == 'yes') {
			$shipingprice = 0;
		} else {
			$shipingprice = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $shiping['primary_cost']);
		}
		$ship_cost += $shipingprice;
		$resultarray1['shipping_price'] = $ship_cost;
		$tax = $this->Taxes->find()->where(['countryid' => $shipping_address['countrycode']])->andWhere(['status' => 'enable'])->all();
		foreach ($tax as $taxes) {
			$tax_cost += $taxes['percentage'];
		}

		if ($quantity == 0 || $item_datas['status'] != "publish" || count($shipping_address) == 0 || count($shiping) == 0) {
			$buyable = "no";
		} else {
			$buyable = "yes";
		}

		$resultarray['items'][0]['buyable'] = $buyable;
		if ($buyable == "no") {
			if ($quantity == 0)
				$cart_message = "Item sold out";
			elseif ($item_datas['status'] != "publish")
				$cart_message = "Item disabled";
			elseif (count($shipping_address) == 0)
				$cart_message = "Shipping not available for this country";
			elseif (count($shiping) == 0)
				$cart_message = "Shipping not available for this country";
		} else
		$cart_message = "";
		$resultarray['items'][0]['cart_message'] = $cart_message;

		$resultarray1['tax'] = round(($tax_cost * $total_cost) / 100, 2);
		$resultarray1['grand_total'] = $resultarray1['item_total'] + $resultarray1['shipping_price'] + $resultarray1['tax'];
		$resultarray1['currency'] = $cur_symbol;
		$resultarray1['items'] = $resultarray['items'];
		$resultarray1['shipping']['shipping_id'] = $shipping_address['shippingid'];
		$user_detail = $this->Users->find()->where(['id' => $shipping_address['userid']])->first();
				//$resultarray1['shipping']['full_name'] = $user_detail['first_name']. ' '.$user_detail['last_name'];
		$resultarray1['shipping']['full_name'] = $shipping_address['name'];
		$resultarray1['shipping']['nick_name'] = $shipping_address['nickname'];
		$resultarray1['shipping']['address1'] = $shipping_address['address1'];
		$resultarray1['shipping']['address2'] = $shipping_address['address2'];
		$resultarray1['shipping']['city'] = $shipping_address['city'];
		$resultarray1['shipping']['state'] = $shipping_address['state'];
		$resultarray1['shipping']['country'] = $shipping_address['country'];
		$resultarray1['shipping']['country_id'] = $shipping_address['countrycode'];
		$resultarray1['shipping']['zipcode'] = $shipping_address['zipcode'];
		$resultarray1['shipping']['phone'] = $shipping_address['phone'];
		if ($shipping_address['shippingid'] == $user_detail['defaultshipping'])
			$resultarray1['shipping']['default'] = "yes";
		else
			$resultarray1['shipping']['default'] = "no";
		echo '{"status":"true","result":' . json_encode($resultarray1) . '}';
		die;
	}



	function getsingleitem($item_id = null, $user_id=null,  $dat = null)
	{
		//$item_id = $_POST['item_id'];
		//$user_id = $_POST['user_id'];
		$this->loadModel('Contactsellers');
		$this->loadModel('Itemreviews');
		$this->loadModel('Sitequeries');
		$this->loadModel('Facebookcoupons');
		$this->loadModel('Forexrates');
		$this->loadModel('Sellercoupons');
		$this->loadModel('Photos');
		$this->loadModel('Currency');
		$this->loadModel('Users');
		$this->loadModel('Searchitems');
		$this->loadModel('Followers');
		$this->loadModel('Items');
		$this->loadModel('Itemfavs');
		$this->loadModel('Storefollowers');
		$this->loadModel('Shops');
		$this->loadModel('Fashionusers');
		$this->loadModel('Sitesettings');
		$this->loadModel('Comments');
		$setngs = $this->Sitesettings->find()->toArray();

		$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();


		

		$resultArray = array();

		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}
		$userDetail = $this->Users->find()->where(['id' => $user_id])->first();



		if (!empty($userDetail))
			$userId = $userDetail['id'];
		else
			$userId = $userId;

		$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();
		if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "") {

			$cur_symbol = $forexrateModel['currency_symbol'];

			$cur = $forexrateModel['price'];
		} else {

			$cur_symbol = $currency_value['currency_symbol'];
			$cur = $currency_value['price'];
		}


		$listitem = TableRegistry::get('Items')->find()->contain('Forexrates')->where(['Items.id' => $item_id])->andWhere(['Items.status' => 'publish'])->first();

		//echo '<pre>'; print_r($listitem); die;

		
		$price = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $listitem['price']);

		//echo '<pre>'; print_r($listitem); die;
		
		$reportUsers = '';
		$process_time = $listitem['processing_time'];
		if ($process_time == '1d') {
			$process_time = "One business day";
		} elseif ($process_time == '2d') {
			$process_time = "Two business days";
		} elseif ($process_time == '3d') {
			$process_time = "Three business days";
		} elseif ($process_time == '4d') {
			$process_time = "Four business days";
		} elseif ($process_time == '2ww') {
			$process_time = "One-Two weeks";
		} elseif ($process_time == '3w') {
			$process_time = "Two-Three weeks";
		} elseif ($process_time == '4w') {
			$process_time = "Three-Four weeks";
		} elseif ($process_time == '6w') {
			$process_time = "Four-Six weeks";
		} elseif ($process_time == '8w') {
			$process_time = "Six-Eight weeks";
		}
		$shareSeller = $listitem['share_coupon'];

		$shareCouponDetail = $this->Facebookcoupons->find()->where(['item_id' => $listitem['id']])->andWhere(['user_id' => $user_id])->all();
		if (count($shareCouponDetail) != 0)
			$shareUser = "yes";
		else
			$shareUser = "no";

		$convertPrice = round($listitem['price'] * $forexrateModel['price'], 2);
		if ($listitem['featured'] == 1)
			$convertdefaultprice = round(($listitem['price'] * 2) * $listitem['price'], 2);
		else
			$convertdefaultprice = round($listitem['price'] * $listitem['price'], 2);

		$resultArray['id'] = $listitem['id'];
		$resultArray['item_title'] = $listitem['item_title'];
		$itemid = base64_encode($listitem['id'] . "_" . rand(1, 9999));
		$resultArray['product_url'] = SITE_URL . 'listing/' . $itemid;
		$resultArray['item_description'] = $listitem['item_description'];
		$resultArray['shipping_time'] = $process_time;
		$resultArray['currency'] = $cur_symbol;
		$resultArray['mainprice'] = $listitem['price'];

		$tdy = strtotime(date("Y-m-d"));

		if (strtotime($listitem['dealdate']) == $tdy && $listitem['discount_type'] == 'daily') {
			$resultArray['deal_enabled'] = 'yes';
			$resultArray['pro_discount'] = 'dailydeal';
			$resultArray['discount_percentage'] = $listitem['discount'];
		} elseif($listitem['discount_type'] == 'regular') {
			$resultArray['deal_enabled'] = 'yes';
			$resultArray['pro_discount'] = 'regulardeal';
			$resultArray['discount_percentage'] = $listitem['discount'];
		}else{
			$resultArray['deal_enabled'] = 'no';
			$resultArray['discount_percentage'] = 0;
		}



		$resultArray['fbshare_discount'] = $listitem['share_discountAmount'];
		$resultArray['valid_till'] = (!isset($listitem['dealdate'])) ? '' : strtotime($listitem['dealdate']) ;
		$resultArray['quantity'] = $listitem['quantity'];
		$resultArray['cod'] = $listitem['cod'];

		$itemDiscount = $this->Sellercoupons->find()->where([
			'sourceid' => $item_id,
			'type'=>'item',
			'remainrange !='=>'0'
			])->order(['id'=>'desc'])->first();

		$categoryDiscount = $this->Sellercoupons->find()->where([
			'sourceid' => $listitem->category_id,
			'sellerid'=>$listitem['user_id'],
			'type'=>'category',
			'remainrange !='=>'0'
			])->order(['id'=>'desc'])->first();

		

		
		$now = strtotime(date('m/d/Y'));
		
		if($now <= strtotime($itemDiscount->validto))
		{
			$itemDiscount = $itemDiscount;
		}else{
			$itemDiscount = '';
		}


		if($now <= strtotime($categoryDiscount->validto))
		{
			$categoryDiscount = $categoryDiscount;
		}else{
			$categoryDiscount = '';
		}

		


		

		//$resultArray['seller_offer'] = (!empty($itemDiscount)) ? $itemDiscount->couponcode.' use this coupon to get extra '.$itemDiscount->couponpercentage.'% discount from '.date("M d", strtotime($itemDiscount->validfrom)).' to '.date("M d", strtotime($itemDiscount->validto)).' limited for first '.$itemDiscount->totalrange : '';

		if((!empty($itemDiscount))){
			$resultArray['seller_offer']['couponcode'] = $itemDiscount->couponcode;
			$resultArray['seller_offer']['couponpercentage'] = $itemDiscount->couponpercentage;
			$resultArray['seller_offer']['validfrom'] = date("M d", strtotime($itemDiscount->validfrom));
			$resultArray['seller_offer']['validto'] = date("M d", strtotime($itemDiscount->validto));	
			$resultArray['seller_offer']['coupon_count'] = $itemDiscount->totalrange;
		}else{
			$resultArray['seller_offer'] = (object) array();
		}
		
		//xxx use this coupon to get extra 10% discount from April 10 to April 15. Limited for first 10 purchases only.
		if((!empty($categoryDiscount))){
			$resultArray['category_offer']['couponcode'] = $categoryDiscount->couponcode;
			$resultArray['category_offer']['couponpercentage'] = $categoryDiscount->couponpercentage;
			$resultArray['category_offer']['validfrom'] = date("M d", strtotime($categoryDiscount->validfrom));
			$resultArray['category_offer']['validto'] = date("M d", strtotime($categoryDiscount->validto));
			$resultArray['category_offer']['coupon_count'] = $categoryDiscount->totalrange;
		}else{
			$resultArray['category_offer'] = (object) array();
		}

		$resultArray['admin_offer'] = '';

		$resultArray['size'] = [];

		if (empty($listitem['size_options'])) {
			$resultArray['size'] = [];
		} else {
			$sizes = json_decode($listitem['size_options'], true);
			$sqkey = 0;
			$setPrice = 0;
			foreach ($sizes['size'] as $val) {
				if (count($sizes['unit'][$val]) > 0) {
					$resultArray['size'][$sqkey]['name'] = $val;
					$resultArray['size'][$sqkey]['qty'] = $sizes['unit'][$val];
					$resultArray['size'][$sqkey]['price'] = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $sizes['price'][$val]);
					if(($sizes['unit'][$val] > 0) && ($setPrice==0))
					{
						$price = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $sizes['price'][$val]);
						$setPrice++;
					}
					$sqkey++;
				}
			}
		}

		if ($currency_value['currency_code'] != $forexrateModel['currency_code'])
			$resultArray['price'] = $price;
		else
			$resultArray['price'] = $price;

		$shop_data = $this->Shops->find()->where(['id' => $listitem['shop_id']])->first();
		$shop_image = $shop_data['shop_image'];

		if ($shop_image == "")
			$shop_image = "usrimg.jpg";

		$resultArray['shop_id'] = $shop_data['id'];
		$resultArray['shop_name'] = $shop_data['shop_name_url'];
		$resultArray['shop_image'] = $img_path . 'media/avatars/thumb350/' . $shop_image;
		$resultArray['shop_address'] = $shop_data['shop_address'];

		$store_follow_status = $this->Storefollowers->find()->where(['store_id' => $shop_data['id']])->andwhere(['follow_user_id' => $user_id])->first();
		if (count($store_follow_status) == 0) {
			$resultArray['store_follow'] = "no";
		} else {
			$resultArray['store_follow'] = "yes";
		}

		if ($listitem['featured'] == 1)
			$convertdefaultprice = round(($listitem['price'] * 2) * $listitem['price'], 2);
		else
			$convertdefaultprice = round($listitem['price'] * $listitem['price'], 2);

		$resultArray['latitude'] = $shop_data['shop_latitude'];
		$resultArray['longitude'] = $shop_data['shop_longitude'];
		$likedcount = $this->Itemfavs->find()->where(['item_id' => $listitem['id']])->count();
		$resultArray['like_count'] = $likedcount;
		$resultArray['reward_points'] = floor($convertdefaultprice);
		$resultArray['share_seller'] = $shareSeller;
		$resultArray['share_user'] = $shareUser;

		if ($listitem['status'] == 'things') {
			$resultArray['buy_type'] = "affiliate";
		} else if ($listitem['status'] == 'publish') {
			$resultArray['buy_type'] = "buy";
		}
		$resultArray['affiliate_link'] = $listitem['bm_redircturl'];
		if ($listitem['status'] == 'publish') {
			$resultArray['approve'] = true;
		} else {
			$resultArray['approve'] = false;
		}

		$item_status = json_decode($listitem['report_flag'], true);

		if (in_array($user_id, $item_status)) {
			$report_status = "yes";
		} else {
			$report_status = "no";

		}
		$resultArray['report'] = $report_status;
		$liked_status = $this->Itemfavs->find()->where(['item_id' => $item_id])->andwhere(['user_id' => $user_id])->first();
		if (count($liked_status) == 0) {
			$resultArray['liked'] = "no";
		} else {
			$resultArray['liked'] = "yes";
		}
		$resultArray['video_url'] = $listitem['videourrl'];


		$photos = $this->Photos->find()->where(['item_id' => $item_id])->all();
		$itemCount = 0;
		$resultArray['photos'] = array();
		$itemCount = 0;
		foreach ($photos as $keys => $photo) {
			if ($listitem['id'] == $photo['item_id']) {
				if ($keys == 0) {
					$resultArray['photos'][$itemCount]['item_url_main_70'] = $img_path . 'media/items/thumb70/' . $photo['image_name'];

				} else {
					$resultArray['photos'][$itemCount]['item_url_main_70'] = $img_path . 'media/items/thumb70/' . $photo['image_name'];

				}

				if ($keys == 0) {
					$image = $img_path . 'media/items/thumb350/' . $photo['image_name'];
					list($width, $height) = getimagesize($image);
					$resultArray['photos'][$itemCount]['item_url_main_350'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];
					$resultArray['photos'][$itemCount]['height'] = $height;
					$resultArray['photos'][$itemCount]['width'] = $width;

				} else {
					$image = $img_path . 'media/items/thumb350/' . $photo['image_name'];
					list($width, $height) = getimagesize($image);
					$resultArray['photos'][$itemCount]['item_url_main_350'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];

					$resultArray['photos'][$itemCount]['height'] = $height;
					$resultArray['photos'][$itemCount]['width'] = $width;

				}

				if ($keys == 0) {
					$resultArray['photos'][$itemCount]['item_url_main_original'] = $img_path . 'media/items/original/' . $photo['image_name'];

				} else {
					$resultArray['photos'][$itemCount]['item_url_main_original'] = $img_path . 'media/items/original/' . $photo['image_name'];

				}

				$itemCount += 1;
			}
		}

		$fashion_data = $this->Fashionusers->find()->where(['itemId' => $item_id])->andWhere(['status' => "YES"])->order(['id' => 'DESC'])->all();
		$resultArray['product_selfies'] = array();
		foreach ($fashion_data as $key => $fashion_datas) {
			$resultArray['product_selfies'][$key]['image_350'] = $img_path . 'media/avatars/thumb350/' . $fashion_datas['userimage'];
			$resultArray['product_selfies'][$key]['image_original'] = $img_path . 'media/avatars/original/' . $fashion_datas['userimage'];
			$resultArray['product_selfies'][$key]['user_id'] = $fashion_datas['user_id'];

			$user_detail1 = $this->Users->find()->where(['id' => $fashion_datas['user_id']])->first();
			$profileimage = $user_detail1['profile_image'];
			if ($profileimage == "") {
				$profileimage = "usrimg.jpg";
			}

			$resultArray['product_selfies'][$key]['user_name'] = $user_detail1['username'];
			$resultArray['product_selfies'][$key]['user_image'] = ($profileimage != '') ? $img_path."media/avatars/thumb70/".$profileimage : $img_path."media/avatars/thumb70/usrimg.jpg";

			//$img_path . 'media/avatars/thumb150/' . $profileimage;
		}

		$Details = $this->Comments->find()->where(['item_id' => $item_id])->order(['id' => 'DESC'])->limit(2);
		$resultArray['recent_comments'] = array();
		foreach ($Details as $key => $details) {
			$resultArray['recent_comments'][$key]['comment_id'] = $details['id'];
			$resultArray['recent_comments'][$key]['comment'] = $details['comments'];
			$resultArray['recent_comments'][$key]['user_id'] = $details['user_id'];
			$user_detail = $this->Users->find()->where(['id' => $details['user_id']])->first();
			$profileimage = $user_detail['profile_image'];
			if ($profileimage == "") {
				$profileimage = "usrimg.jpg";
			}
			$resultArray['recent_comments'][$key]['user_image'] = $img_path . 'media/avatars/thumb150/' . $profileimage;
			$resultArray['recent_comments'][$key]['user_name'] = $user_detail['username'];
			$resultArray['recent_comments'][$key]['full_name'] = $user_detail['first_name'] . ' ' . $user_detail['last_name'];
		}
		$items_data = $this->Items->find('all', array(
			'conditions' => array(
				'Items.shop_id' => $shop_data['id'],
				'Items.id <>' => $item_id,
				'Items.status' => 'publish',
			),
			'limit' => 10,

		))->contain('Forexrates');
		$items_data1 = $this->Items->find('all', array(
			'conditions' => array(
				'Items.category_id' => $listitem['category_id'],
				'Items.id <>' => $item_id,
				'Items.status' => 'publish',
			),
			'limit' => 10,

		))->contain('Forexrates');

		$items_fav_data = $this->Itemfavs->find()->where(['user_id' => $user_id])->all();
		if (count($items_fav_data) > 0) {
			foreach ($items_fav_data as $favitems) {
				$favitems_ids[] = $favitems['item_id'];
			}
		} else {
			$favitems_ids = array();
		}

		$sellerData = $this->Users->find()->where(['id' => $listitem['user_id']])->first();
		$resultArray['average_rating'] = $sellerData['seller_ratings'];

		$resultArray['store_products'] = $this->convertJsonHome($items_data, $favitems_ids, $user_id, 1);
		$resultArray['similar_products'] = $this->convertJsonHome($items_data1, $favitems_ids, $user_id, 1);

		$inputArray = array('item_id'=>$item_id);
		$resultArray['recent_questions'] = $this->getlatestproduct_faq($inputArray);

		//$resultArray['item_reviews'] = $this->getitemreviews($item_id);

		$itemreviewTable = TableRegistry::get('Itemreviews');
		$reviewData = $this->Itemreviews->find('all', array(
				'conditions' => array(
					'itemid' => $item_id
				),
				'limit' => 2,
				'offset' => 0,
				'order' => 'id DESC',
			))->all();


		$reviewCount = $this->Itemreviews->find('all', array(
				'conditions' => array(
					'itemid' => $item_id
				),
				'order' => 'id DESC',
			))->count();


		$getAvgrat = $this->getAverage($item_id);
		$result = array();


		//$userImage = $img_path . "media/avatars/thumb70/" . $userImage;
		
		foreach($reviewData as $key=>$eachreview)
		{
			$user_data = $this->Users->find()->where(['id' => $eachreview['userid']])->first();
			$result[$key]['user_id'] = $eachreview['userid'];
			$result[$key]['user_name'] = $user_data['username'];
			$result[$key]['user_image'] = ($user_data['profile_image'] != '') ? $img_path . "media/avatars/thumb70/".$user_data['profile_image'] : $img_path . "media/avatars/thumb70/usrimg.jpg";
			$result[$key]['id'] = $eachreview['orderid'];
			$result[$key]['review_title'] = $eachreview['review_title'];
			$result[$key]['rating'] = $eachreview['ratings'];
			$result[$key]['review'] = $eachreview['reviews'];
		}

		$datanewSourceObject = ConnectionManager::get('default');
    	$ratingstmt = $datanewSourceObject->execute("SELECT count(*) as Total, round(ratings) as ratings from fc_itemreviews where itemid=".$item_id." group by ratings order by ratings desc
		")->fetchAll('assoc');

		//echo '<pre>'; print_r($ratingstmt); die;

    	$byrateGroup = $this->group_by("ratings", $ratingstmt);

    	//echo '<pre>'; print_r($byrateGroup); die;
		$rating_count = ($byrateGroup[5][0]['Total']+$byrateGroup[4][0]['Total']+$byrateGroup[3][0]['Total']+$byrateGroup[2][0]['Total']+$byrateGroup[1][0]['Total']);
		
		$five = (empty($byrateGroup[5][0]['Total'])) ? 0 : $byrateGroup[5][0]['Total'] ;
		$four = (empty($byrateGroup[4][0]['Total'])) ? 0 : $byrateGroup[4][0]['Total'] ;
		$three = (empty($byrateGroup[3][0]['Total'])) ? 0 : $byrateGroup[3][0]['Total'] ;
		$two = (empty($byrateGroup[2][0]['Total'])) ? 0 : $byrateGroup[2][0]['Total'] ;
		$one = (empty($byrateGroup[1][0]['Total'])) ? 0 : $byrateGroup[1][0]['Total'] ;

		$resultArray['item_reviews'] = array(
			'review_count'=>$reviewCount,
			'rating'=>$listitem['avg_rating'],
			'rating_count'=>$rating_count,
			'five'=>$five,
			'four'=>$four,
			'three'=>$three,
			'two'=>$two,
			'one'=>$one,
			'result'=>$result
			);
		
		$orderstable = TableRegistry::get('Orders');
		$orderitemstable = TableRegistry::get('OrderItems');

		$ordersModel = $orderstable->find('all')->where(['userid' => $user_id,'status'=>'Delivered'])->order(['orderid DESC'])->all();
		$orderid = array();
        foreach ($ordersModel as $value) {
            $orderid[] = $value['orderid'];
        }

        if(!empty($orderid))
        {
        	$orderitemModel = $orderitemstable->find('all')->where(['itemid'=>$item_id,'orderid IN' => $orderid])->order(['orderid DESC'])->first();	
        	$resultArray['order_id'] = (isset($orderitemModel->orderid)) ? $orderitemModel->orderid : '';
        	if(isset($orderitemModel->orderid)){
        		$get_review = TableRegistry::get('Itemreviews');
				$firstreviewData = $this->Itemreviews->find('all', array(
						'conditions' => array(
							'itemid' => $item_id,
							'orderid'=> $orderitemModel->orderid
						),
						'order' => 'id DESC',
					))->first();
				$resultArray['review_id'] = (isset($firstreviewData->id)) ? $firstreviewData->id : '';
        	}
        }else{
        	$resultArray['order_id'] = '';
        	$resultArray['review_id'] = '';
        }

        //echo '<pre>'; print_r($resultArray); die;
		return $resultArray;
	}

	function popularStore()
	{

		$popular_stores = $this->popularStores();
		$category = $this->category();
		echo '{"status":"true","result":' . $popular_stores . '}';
		die;

	}
	function createFeed()
	{
		$this->loadModel('Logs');
		$this->loadModel('Followers');
		$this->loadModel('Hashtags');
		$this->loadModel('Comments');
		$this->loadModel('Users');
		$this->loadModel('Sitesettings');
		$logusrid = $_POST['user_id'];
		if ($this->disableusercheck($logusrid) == 0) {
			echo '{"status":"error","message":"The user has been blocked by admin"}';
			die;
		}
		$statusimage = $_POST['image'];
		$postmessage = $_POST['message'] . " ";
		$comment = $_POST['message'] . " ";
		$loguser = $this->Users->find()->where(['id' => $logusrid])->first();
		if ($logusrid == "") {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		}
		if (count($loguser) == 0) {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		}
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}
		$image['user']['image'] = $loguser['profile_image'];
		$logusername = $loguser['username'];
		$logusernameurl = $loguser['username_url'];
		$image['user']['link'] = SITE_URL . "people/" . $logusernameurl;
		if ($statusimage != '') {
			$image['status']['image'] = $statusimage;
			$image['status']['link'] = '';
			$statusimageresult = $img_path . 'media/status/original/' . $statusimage;
		} else
		$statusimageresult = '';
		$loguserimage = json_encode($image);
		/******** Add the @user and #hashtags in feeds *********/
		$usedHashtag = '';
		$oldHashtags = array();
		preg_match_all('/#([\S]*?)(?=\s)/', $comment, $hashmatch);

		if (!empty($hashmatch)) {
			foreach ($hashmatch[1] as $hashtag) {
				$cleanedHashtag = preg_replace('/[^A-Za-z0-9\-]/', '', $hashtag);
				if (!empty($cleanedHashtag) && $cleanedHashtag != '') {
					$hashtag = $cleanedHashtag;
					if ($usedHashtag == '') {
						$usedHashtag = $hashtag;
					}
					$usedHashtag .= ',' . $hashtag;
					$comment = str_replace('#' . $hashtag . " ", '<span class="hashatcolor">#</span><a href="' . SITE_URL . 'hashtag/' . $hashtag . '">' . $hashtag . '</a> ', $comment);
				}
			}
			$hashTags = explode(',', $usedHashtag);
			$hashtagsModel = $this->Hashtags->find()->where(['hashtag IN' => $hashTags])->all();
			if (!empty($hashtagsModel)) {
				foreach ($hashtagsModel as $hashtags) {
					$id = $hashtags['id'];
					$count = $hashtags['usedcount'] + 1;
					$this->Hashtags->updateAll(array('usedcount' => $count), array('id' => $id));
					$oldHashtags[] = $hashtags['hashtag'];
				}
			}
			foreach ($hashTags as $hashtag) {
				if (!in_array($hashtag, $oldHashtags)) {
					$hashtag_data = $this->Hashtags->newEntity();
					$hashtag_data->hashtag = $hashtag;
					$hashtag_data->usedcount = 1;
					$this->Hashtags->save($hashtag_data);
				}
			}
		}
		preg_match_all('/@([\S]*?)(?=\s)/', $comment, $atmatch);
			//echo "<pre>"; print_r($match);
		$mentionedUsers = "";
		if (!empty($atmatch)) {
			foreach ($atmatch[1] as $atuser) {
				$cleanedAtUser = preg_replace('/[^A-Za-z0-9\-]/', '', $atuser);
				if (!empty($cleanedAtUser) && $cleanedAtUser != '') {
					$atuser = $cleanedAtUser;
					$comment = str_replace('@' . $atuser . " ", '<span class="hashatcolor">@</span><a href="' . SITE_URL . 'people/' . $atuser . '">' . $atuser . '</a> ', $comment);
					$mentionedUsers = $mentionedUsers != "" ? "," . $atuser : $atuser;
				}
			}
		}
		$comment_data = $this->Comments->newEntity();
		$comment_data->user_id = $logusrid;
		$comment_data->item_id = "-1";
		$comment_data->comments = $comment;
		$comment_data->created_on = date("Y-m-d H:i:s");
		$commentResult = $this->Comments->save($comment_data);
		$comment_id = $commentResult->id;
		$flwrscnt = $this->Followers->find()->where(['user_id' => $logusrid])->all();
		if (count($flwrscnt) == 0)
			$flwrusrids = array();
		if (count($flwrscnt) != 0) {

			foreach ($flwrscnt as $flwss) {
				$flwssid = $flwss['follow_user_id'];
				$flwrusrids[$flwssid] = $flwss['follow_user_id'];
			}
		} else {
			$flwrusrids = 0;
		}

		$logusernameurl = $loguser['username_url'];
		$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logusername . "</a>";
		$notifymsg = $loguserlink . " -___-posted a status";
		
		$logdetails = $this->addlog('status', $logusrid, $flwrusrids, $comment_id, $notifymsg, $postmessage, $loguserimage);

		$logstable = TableRegistry::get('Logs');
		$userlogd = $logstable->find('all')->where(['userid' => $logusrid])
		->where(['type' => 'status'])->order(['id DESC'])->limit(1)->all();
		foreach ($flwrusrids as $flwww) {
			$useriddd = $flwww;
			$userdevicestable = TableRegistry::get('Userdevices');
			$userddett = $userdevicestable->find('all')->where(['user_id' => $useriddd])->all();
			if (!empty($userddett)) {
				foreach ($userddett as $userdet) {
					$deviceTToken = $userdet['deviceToken'];
					$badge = $userdet['badge'];
					$badge += 1;
					$querys = $userdevicestable->query();
					$querys->update()
					->set(['badge' => $badge])
					->where(['deviceToken' => $deviceTToken])
					->execute();
					if (isset($deviceTToken)) {
						$pushMessage['type'] = 'post_status';
						$pushMessage['user_id'] = $loguser['id'];
						$pushMessage['feed_id'] = $comment_id;
						$pushMessage['user_name'] = $loguser['username'];
						$pushMessage['user_image'] = $userImg;
						$user_detail = TableRegistry::get('Users')->find()->where(['id' => $useriddd])->first();
						I18n::locale($user_detail['languagecode']);
						$pushMessage['message'] = __d('user', "posted a status");
						$messages = json_encode($pushMessage);
						$this->pushnot($deviceTToken, $messages, $badge);
					}
				}
			}
		}
		$userlogd = $this->Logs->find()->where(['userid' => $logusrid])->andWhere(['type' => 'status'])->order(['id DESC'])->toArray();
		$logid = $userlogd[0]['id'];
		if ($mentionedUsers != "") {
			$mentionedUsers = explode(",", $mentionedUsers);
			foreach ($mentionedUsers as $musers) {
				$userModel = $this->Users->find()->where(['username' => $musers])->first();
				$notificationSettings = json_decode($userModel['push_notifications'], true);
				$notifyto = $userModel['id'];
				if ($notificationSettings['somone_mentions_push'] == 1 && $logusrid != $notifyto) {
					$logusername = $loguser['username'];
					$logusernameurl = $loguser['username_url'];
					$liked = $setngs[0]['liked_btn_cmnt'];
					$userImg = $loguser['profile_image'];
					if (empty($userImg)) {
						$userImg = 'usrimg.jpg';
					}
					$image['user']['image'] = $userImg;
					$image['user']['link'] = SITE_URL . "people/" . $logusernameurl;
					$loguserimage = json_encode($image);
					$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logusername . "</a>";
					$loglink = "<a href='" . SITE_URL . "livefeed/" . $logid . "'>" . $logid . "</a>";;
					$notifymsg = $loguserlink . " -___-mentioned you in a status: -___- " . $loglink;
					$itemid = '-1';
					$logdetails = $this->addlog('mentioned', $logusrid, $notifyto, $comment_id, $notifymsg, $comment, $loguserimage, $itemid);
					$userdevicestable = TableRegistry::get('Userdevices');
					$userddett = $userdevicestable->find('all')->where(['user_id' => $notifyto])->all();
					foreach ($userddett as $userdet) {
						$deviceTToken = $userdet['deviceToken'];
						$badge = $userdet['badge'];
						$badge += 1;
						$querys = $userdevicestable->query();
						$querys->update()
						->set(['badge' => $badge])
						->where(['deviceToken' => $deviceTToken])
						->execute();

						if (isset($deviceTToken)) {
							$pushMessage['type'] = 'mention_status';
							$pushMessage['user_id'] = $loguser['id'];
							$pushMessage['feed_id'] = $comment_id;
							$pushMessage['user_name'] = $loguser['username'];
							$pushMessage['user_image'] = $userImg;
							$user_detail = TableRegistry::get('Users')->find()->where(['id' => $notifyto])->first();
							I18n::locale($user_detail['languagecode']);
							$pushMessage['message'] = __d('user', "mentioned you in the status");
							$messages = json_encode($pushMessage);
							$this->pushnot($deviceTToken, $messages, $badge);
						}
					}

				}

			}
		}
		$commentEncoded = urldecode($postmessage);
		$resultArray['feed_id'] = $logid;
		$resultArray['message'] = $commentEncoded;
		$resultArray['status_image'] = $statusimageresult;
		list($width, $height) = getimagesize($image);
		$resultArray['height'] = $height;
		$resultArray['width'] = $width;
		echo '{"status":"true","result":' . json_encode($resultArray) . '}';
		die;
	}

	function postLike()
	{
		$this->loadModel('Likedusers');
		$this->loadModel('Logs');
		$logid = $_POST['feed_id'];
		$userid = $_POST['user_id'];
		$loguser = $this->Users->find()->where(['id' => $userid])->first();
		$log_datas = $this->Logs->find()->where(['id' => $logid])->first();//ById($logid);
		$logImage = json_decode($log_datas['image'], true);
		$count = $log_datas['likecount'];
		$likedusers = $this->Likedusers->find()->where(['userid' => $userid])->andWhere(['statusid' => $logid])->first();
		if (!empty($logid) && !empty($userid)) {
			if (empty($likedusers)) {
				$count = $count + 1;
				$this->Logs->updateAll(array('likecount' => $count), array('id' => $logid));
				$likeduser_data = $this->Likedusers->newEntity();
				$likeduser_data->userid = $userid;
				$likeduser_data->statusid = $logid;
				$checklike_logs = $this->Logs->find()->where(['type' => 'favorite'])->andWhere(['userid' => $userid])->andWhere(['notifyto' => $log_datas['userid']])->andWhere(['sourceid' => $logid])->count();
				$this->Likedusers->save($likeduser_data);
				$notifyto = $log_datas['userid'];
				$userstable = TableRegistry::get('Users');
				$users = $userstable->find()->where(['id' => $notifyto])->first();
				$notificationSettings = json_decode($users['push_notifications'], true);
				$logusername = $loguser['username'];
				$logfirstname = $loguser['first_name'];
				$logusernameurl = $loguser['username_url'];
				$itemname = $userdatasall['item_title'];
				$item_url = base64_encode($itemid . "_" . rand(1, 9999));
				$itemurl = $userdatasall['item_title_url'];
				$liked = $setngs['liked_btn_cmnt'];
				$userImg = $loguser['profile_image'];
				if (empty($userImg)) {
					$userImg = 'usrimg.jpg';
				}
				$image['user']['image'] = $userImg;
				$image['user']['link'] = SITE_URL . "people/" . $logusernameurl;
				$image['item']['image'] = $userdatasall['photos'][0]['image_name'];
				$image['item']['link'] = SITE_URL . "listing/" . $item_url;
				$image['status']['image'] = $logImage['status']['image'];
				$image['status']['message'] = $log_datas['message'];
				$loguserimage = json_encode($image);
				$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logfirstname . "</a>";
				$productlink = "<a href='" . SITE_URL . "listing/" . $itemid . "/" . $itemurl . "'>" . $itemname . "</a>";
				$notifymsg = $loguserlink . "  -___-liked your status-___- " . $logid;
				if ($checklike_logs == 0 && $userid != $notifyto) {
					$logdetails = $this->addlog('favorite', $userid, $notifyto, $logid, $notifymsg, $log_datas['message'], $loguserimage, 0);
				}

				if ($userid != $notifyto) {
					$userdevicestable = TableRegistry::get('Userdevices');
					$userddett = $userdevicestable->find('all')->where(['user_id' => $notifyto])->all();
					foreach ($userddett as $userdet) {
						$deviceTToken = $userdet['deviceToken'];
						$badge = $userdet['badge'];
						$badge += 1;
						$querys = $userdevicestable->query();
						$querys->update()
						->set(['badge' => $badge])
						->where(['deviceToken' => $deviceTToken])
						->execute();
						if (isset($deviceTToken)) {
							$pushMessage['type'] = 'like_status';
							$pushMessage['user_id'] = $loguser['id'];
							$pushMessage['feed_id'] = $logid;
							$pushMessage['user_name'] = $loguser['username'];
							$pushMessage['user_image'] = $userImg;
							$user_detail = TableRegistry::get('Users')->find()->where(['id' => $notifyto])->first();
							I18n::locale($user_detail['languagecode']);
							$pushMessage['message'] = __d('user', "liked your status");
							$messages = json_encode($pushMessage);
							$this->pushnot($deviceTToken, $messages, $badge);
						}
					}
				}

				echo '{"status":"true","message":"Post Liked"}';
				die;
			} else {
				$count = $count - 1;
				$this->Logs->updateAll(array('likecount' => $count), array('id' => $logid));
				$this->Likedusers->deleteAll(array('userid' => $userid, 'statusid' => $logid), false);
				echo '{"status":"true","message":"Post Unliked"}';
				die;
			}
		} else {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		}
	}

	function likedUsers()
	{

		$logid = $_POST['feed_id'];
		$userId = $_POST['user_id'];
		$this->loadModel('Likedusers');
		$this->loadModel('Followers');
		$this->loadModel('Users');
		$offset = 0;
		$limit = 10;
		if (isset($_POST['offset'])) {
			$offset = $_POST['offset'];
		}
		if (isset($_POST['limit'])) {
			$limit = $_POST['limit'];
		}

		$likedusers = $this->Likedusers->find('all', array(
			'conditions' =>
			array('statusid' => $logid),
			'offset' => $offset,
			'limit' => $limit
		));

		$flwrscnt = $this->Followers->find()->where(['follow_user_id' => $userId])->all();//'all',array('conditions'=>array('follow_user_id'=>$userId)));
		//	foreach($flwrscnt as $flwrs)
		//	{
		//		$flwrusrids[] = $flwrs['user_id'];
		//	}

		if (count($flwrscnt) != 0) {

			foreach ($flwrscnt as $flwrs) {
				$flwrusrids[] = $flwrs['user_id'];
			}
		} else {
			$flwrusrids = 0;
		}

		foreach ($likedusers as $key => $likers) {
			$userData = $this->Users->find()->where(['id' => $likers['userid']])->first();
			if (!empty($userData["profile_image"])) {
				$path = $userData['profile_image'];
			} else {
				$path = 'usrimg.jpg';
			}
			if (SITE_URL == $_SESSION['media_url']) {
				$img_path = $_SESSION['media_url'];
			} else {
				$img_path = $_SESSION['media_url'];
			}
			$likeuserid = $userData['id'];
			$resultarray[$key]['user_id'] = $userData['id'];
			$resultarray[$key]['full_name'] = $userData['first_name'] . ' ' . $userData['last_name'];
			$resultarray[$key]['user_name'] = $userData['username'];

			$resultarray[$key]['user_image'] = $img_path . 'media/avatars/thumb350/' . $path;

			if (in_array($likeuserid, $flwrusrids))
				$resultarray[$key]['status'] = 'unfollow';
			else
				$resultarray[$key]['status'] = 'follow';

		}
		if (empty($resultarray)) {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		}
		echo '{"status":"true","result":' . json_encode($resultarray) . '}';
		die;
	}

	function postComment()
	{
		$this->loadModel('Feedcomments');
		$this->loadModel('Items');
		$this->loadModel('Users');
		$this->loadModel('Logs');
		$this->loadModel('Hashtags');
		$this->loadModel('Sitesettings');
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
		if (SITE_URL == $_SESSION['media_url']) {
			$img_path = $_SESSION['media_url'];
		} else {
			$img_path = $_SESSION['media_url'];
		}
		if (isset($_POST)) {
			$userId = $_POST['user_id'];
			$feedId = $_POST['feed_id'];
			$pushcomment = $_POST['comment'] . " ";
			$comment = $_POST['comment'] . " ";
			$usedHashtag = '';
			$oldHashtags = array();
			preg_match_all('/#([\S]*?)(?=\s)/', $comment, $hashmatch);
				//echo "<pre>"; print_r($hashmatch);
			if (!empty($hashmatch)) {
				foreach ($hashmatch[1] as $hashtag) {
					$cleanedHashtag = preg_replace('/[^A-Za-z0-9\-]/', '', $hashtag);
					if (!empty($cleanedHashtag) && $cleanedHashtag != '') {
						$hashtag = $cleanedHashtag;
						if ($usedHashtag == '') {
							$usedHashtag = $hashtag;
						}
						$usedHashtag .= ',' . $hashtag;
						$comment = str_replace('#' . $hashtag . " ", '<span class="hashatcolor">#</span><a href="' . SITE_URL . 'hashtag/' . $hashtag . '">' . $hashtag . '</a> ', $comment);
					}
				}
				$hashTags = explode(',', $usedHashtag);
				$hashtagsModel = $this->Hashtags->find()->where(['hashtag IN' => $hashTags])->all();
				if (!empty($hashtagsModel)) {
					foreach ($hashtagsModel as $hashtags) {
						$id = $hashtags['id'];
						$count = $hashtags['usedcount'] + 1;
						$this->Hashtags->updateAll(array('usedcount' => $count), array('id' => $id));
						$oldHashtags[] = $hashtags['hashtag'];
					}
				}
				foreach ($hashTags as $hashtag) {
					if (!in_array($hashtag, $oldHashtags)) {
						$hashtag_data = $this->Hashtags->newEntity();
						$hashtag_data->hashtag = $hashtag;
						$hashtag_data->usedcount = 1;
						$this->Hashtags->save($hashtag_data);
					}
				}
			}
			preg_match_all('/@([\S]*?)(?=\s)/', $comment, $atmatch);
				//echo "<pre>"; print_r($match);
			$mentionedUsers = "";
			if (!empty($atmatch)) {
				foreach ($atmatch[1] as $atuser) {
					$cleanedAtUser = preg_replace('/[^A-Za-z0-9\-]/', '', $atuser);
					if (!empty($cleanedAtUser) && $cleanedAtUser != '') {
						$atuser = $cleanedAtUser;
						$comment = str_replace('@' . $atuser . " ", '<span class="hashatcolor">@</span><a href="' . SITE_URL . 'people/' . $atuser . '">' . $atuser . '</a> ', $comment);
						$mentionedUsers = $mentionedUsers != "" ? "," . $atuser : $atuser;
					}
				}
			}

			$userdatasall = $this->Users->find()->where(['id' => $userId])->first();//ById($userId);
			$loguser[0] = $userdatasall;
			$commentEncoded = urldecode($pushcomment);
			$userModel = $this->Users->find()->where(['id' => $userId])->first();
			$path = $img_path . "media/avatars/thumb70/";
			$username = $userModel['username'];
			$fullname = $userModel['first_name'] . ' ' . $userModel['last_name'];
			if (!empty($userModel["profile_image"])) {
				$path .= $userModel['profile_image'];
			} else {
				$path .= 'usrimg.jpg';
			}
			$username = $userModel['username'];
			$feedcomment_data = $this->Feedcomments->newEntity();
			$feedcomment_data->userid = $userId;
			$feedcomment_data->statusid = $feedId;
			$feedcomment_data->comments = $comment;
			$feedcomment_dataresult = $this->Feedcomments->save($feedcomment_data);
			$id = $feedcomment_dataresult->id;
			if ($mentionedUsers != "") {
				$mentionedUsers = explode(",", $mentionedUsers);
				foreach ($mentionedUsers as $musers) {
					$userModel = $this->Users->find()->where(['username' => $musers])->first();
					$notificationSettings = json_decode($userModel['push_notifications'], true);
					$notifyto = $userModel['id'];
					if ($notificationSettings['somone_mentions_push'] == 1 && $userId != $notifyto) {
						$logusername = $loguser[0]['username'];
						$logfirstname = $loguser[0]['first_name'];
						$logusernameurl = $loguser[0]['username_url'];
						$liked = $setngs[0]['liked_btn_cmnt'];
						$userImg = $loguser[0]['profile_image'];
						if (empty($userImg)) {
							$userImg = 'usrimg.jpg';
						}
						$image['user']['image'] = $userImg;
						$image['user']['link'] = SITE_URL . "people/" . $logusernameurl;
						$loguserimage = json_encode($image);
						$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logusername . "</a>";
						$loglink = "<a href='" . SITE_URL . "livefeed/" . $feedId . "'>" . $feedId . "</a>";
						$notifymsg = $loguserlink . " -___-mentioned you in a comment on : " . $loglink;
						$itemid = '-1';
						$logdetails = $this->addlog('mentioned', $userId, $notifyto, $id, $notifymsg, $comment, $loguserimage, $itemid);
						$userdevicestable = TableRegistry::get('Userdevices');
						$userddett = $userdevicestable->find('all')->where(['user_id' => $notifyto])->all();
						foreach ($userddett as $userdet) {
							$deviceTToken = $userdet['deviceToken'];
							$badge = $userdet['badge'];
							$badge += 1;
							$querys = $userdevicestable->query();
							$querys->update()
							->set(['badge' => $badge])
							->where(['deviceToken' => $deviceTToken])
							->execute();
							if (isset($deviceTToken)) {
								$pushMessage['type'] = 'mention_status';
								$pushMessage['user_id'] = $loguser[0]['id'];
								$pushMessage['feed_id'] = $id;
								$pushMessage['user_name'] = $loguser[0]['username'];
								$pushMessage['user_image'] = $userImg;
								$user_detail = TableRegistry::get('Users')->find()->where(['id' => $notifyto])->first();
								I18n::locale($user_detail['languagecode']);
								$pushMessage['message'] = __d('user', "mentioned you in the status");
								$messages = json_encode($pushMessage);
								$this->pushnot($deviceTToken, $messages, $badge);
							}
						}

					}
				}
			}
			$log_datas = $this->Logs->find()->where(['id' => $feedId])->first();//ById($feedId);
			$counts = $log_datas['commentcount'];
			$counts = $counts + 1;
			$this->Logs->updateAll(array('commentcount' => $counts), array('id' => $feedId));
			$notifyto = $log_datas['userid'];
			$userstable = TableRegistry::get('Users');
			$users = $userstable->find()->where(['id' => $notifyto])->first();
			$notificationSettings = json_decode($users['push_notifications'], true);
			$logusername = $loguser[0]['username'];
			$logfirstname = $loguser[0]['first_name'];
			$logusernameurl = $loguser[0]['username_url'];
			$itemname = $userdatasall['item_title'];
			$item_url = base64_encode($itemid . "_" . rand(1, 9999));
			$itemurl = $userdatasall['item_title_url'];
			$liked = $setngs['liked_btn_cmnt'];
			$userImg = $loguser[0]['profile_image'];
			if (empty($userImg)) {
				$userImg = 'usrimg.jpg';
			}
			$image['user']['image'] = $userImg;
			$image['user']['link'] = SITE_URL . "people/" . $logusernameurl;
			$image['item']['image'] = $userdatasall['photos'][0]['image_name'];
			$image['item']['link'] = SITE_URL . "listing/" . $item_url;
			$loguserimage = json_encode($image);
			$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logfirstname . "</a>";
			$productlink = "<a href='" . SITE_URL . "listing/" . $itemid . "/" . $itemurl . "'>" . $itemname . "</a>";
			$notifymsg = $loguserlink . "  -___-commented on your status-___- " . $feedId;
			$logdetails = $this->addlog('comment', $userId, $notifyto, $feedId, $notifymsg, null, $loguserimage, 0);

			$userdevicestable = TableRegistry::get('Userdevices');
			$userddett = $userdevicestable->find('all')->where(['user_id' => $notifyto])->all();

			foreach ($userddett as $userdet) {
				$deviceTToken = $userdet['deviceToken'];
				$badge = $userdet['badge'];
				$badge += 1;

				$querys = $userdevicestable->query();
				$querys->update()
				->set(['badge' => $badge])
				->where(['deviceToken' => $deviceTToken])
				->execute();

				if (isset($deviceTToken)) {
					$pushMessage['type'] = 'comment_status';
					$pushMessage['user_id'] = $loguser[0]['id'];
					$pushMessage['feed_id'] = $feedId;
					$pushMessage['user_name'] = $loguser[0]['username'];
					$pushMessage['user_image'] = $userImg;
					$user_detail = TableRegistry::get('Users')->find()->where(['id' => $notifyto])->first();
					I18n::locale($user_detail['languagecode']);
					$pushMessage['message'] = __d('user', "commented on your status");
					$messages = json_encode($pushMessage);
					$this->pushnot($deviceTToken, $messages, $badge);
				}
			}

			echo '{"status":"true","comment_id":"' . $id . '","comment":"' . $commentEncoded . '","user_id":"' . $userId . '","user_img":"' . $path . '","user_name":"' . $username . '","full_name":"' . $fullname . '"}';
			die;
		} else {
			echo '{"status":"false","message":"Get Empty"}';
			die;
		}
	}

	function getFeedcomment()
	{
		$this->loadModel('Feedcomments');
		$this->loadModel('Users');
		$feedId = $_POST['feed_id'];
		$offset = 0;
		$limit = 10;
		if (!empty($_POST['offset'])) {
			$offset = $_POST['offset'];
		}
		if (!empty($_POST['limit'])) {
			$limit = $_POST['limit'];
		}
		$feedcomments = $this->Feedcomments->find('all', array('conditions' =>
			array('statusid' => $feedId), 'limit' => $limit, 'offset' => $offset, 'order' => 'id DESC'));
		foreach ($feedcomments as $key => $feeds) {
			$resultarray[$key]['comment_id'] = $feeds['id'];
			$resultarray[$key]['comment'] = $feeds['comments'];
			$resultarray[$key]['user_id'] = $feeds['userid'];
			if ($this->disableusercheck($feeds['userid']) == 0) {
				echo '{"status":"error","message":"The user has been blocked by admin"}';
				die;
			}
			$user_data = $this->Users->find()->where(['id' => $feeds['userid']])->first();

			if (!empty($user_data["profile_image"])) {
				$path = $user_data['profile_image'];
			} else {
				$path = 'usrimg.jpg';
			}
			if (SITE_URL == $_SESSION['media_url']) {
				$img_path = $_SESSION['media_url'];
			} else {
				$img_path = $_SESSION['media_url'];
			}
			$resultarray[$key]['user_image'] = $img_path . 'media/avatars/thumb150/' . $path;
			$resultarray[$key]['user_name'] = $user_data['username'];
			$resultarray[$key]['full_name'] = $user_data['first_name'] . $user_data['last_name'];
		}
		if (empty($resultarray)) {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		}
		$resultArray = json_encode($resultarray);
		echo '{"status":"true","result":' . $resultArray . '}';
		die;
	}

	function checkin()
	{

		$userId = $_POST['user_id'];

		$storeId = $_POST['store_id'];
		$message = $_POST['message'];
		$this->loadModel('Users');
		$this->loadModel('Logs');
		$this->loadModel('Shops');
		$this->loadModel('Followers');
		$this->loadModel('Sitesettings');

		$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
		if (!empty($userId) && !empty($storeId)) {
			$loguser = $this->Users->find()->where(['id' => $userId])->first();//ById($userId);

			$image['user']['image'] = $loguser['profile_image'];

			$logusername = $loguser['username'];
			$logusernameurl = $loguser['username_url'];
			$image['user']['link'] = SITE_URL . "people/" . $logusernameurl;
			if ($loguser['profile_image'] != '') {
				$image['status']['image'] = $statusimage;
				$image['status']['link'] = '';
			}
			$loguserimage = json_encode($image);

			$flwrscnt = $this->Followers->find()->where(['user_id' => $userId])->all();//flwrscnt($userId);

			$flwrusrids = array();
			if (count($flwrscnt) != 0) {

				foreach ($flwrscnt as $flwss) {
					$flwssid = $flwss['follow_user_id'];
					$flwrusrids[$flwssid] = $flwss['follow_user_id'];
				}
			} else {
				$flwrusrids = 0;
			}

			$logdatas = $this->Logs->find()->where(['userid' => $userId, ['type' => 'checkin']])->andWhere(['sourceid' => $storeId])->order(['cdate DESC'])->toArray();//all',array('conditions'=>array('userid'=>$userId,'type'=>'checkin','sourceid'=>$storeId),'order'=>array('Log.cdate'=>'desc')));

			if (count($logdatas) == 0) {
				$userdata = $this->Users->find()->where(['id' => $userId])->first();//first',array('conditions'=>array('User.id'=>$userId)));
				$credit = $userdata['credit_points'];
				$checkincredit = $setngs[0]['checkin_credit'];
				$newcreditpoints = $credit + $checkincredit;
				$this->Users->updateAll(array('credit_points' => $newcreditpoints), array('id' => $userId));
			} else {
				$lastdate = $logdatas[0]['cdate'];

				$currentdate = time();
				$hoursdate = ($currentdate - $lastdate) / 3600;
				if ($hoursdate > 24) {
					$userdata = $this->Users->find()->where(['id' => $userId])->first();//first',array('conditions'=>array('User.id'=>$userId)));
					$credit = $userdata['credit_points'];

					$checkincredit = $setngs[0]['checkin_credit'];

					$newcreditpoints = $credit + $checkincredit;

					$this->Users->updateAll(array('credit_points' => $newcreditpoints), array('id' => $userId));
				}
			}

			$shopdatas = $this->Shops->find()->where(['id' => $storeId])->first();//first',array('conditions'=>array('Shop.id'=>$storeId)));

			$shopuserid = $shopdatas['user_id'];
			$shopnameurls = $shopdatas['shop_name_url'];
			$shopname = $shopdatas['shop_name'];

			$shopnameurl = '<a href="' . SITE_URL . 'stores/' . $shopuserid . '/' . $shopnameurls . '">' . $shopname . '</a>';
			$logusernameurl = $loguser['username_url'];
			$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logusername . "</a>";
			$notifymsg = $loguserlink . " -___-checkin store - $shopnameurl";
			$logdetails = $this->addlog('checkin', $userId, $flwrusrids, $storeId, $notifymsg, $message, $loguserimage);
			echo '{"status":"true","message":"Posted successfully"}';
			die;
		} else {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		}
	}

	function sharePost()
	{

		$logid = $_POST['feed_id'];
		$this->loadModel('Logs');
		$userid = $_POST['user_id'];
		if (!empty($logid) && !empty($userid)) {
			$log_data = $this->Logs->find()->where(['id' => $logid])->first();//',array('conditions'=>array('Log.id'=>
			if (count($log_data) == 0) {
				echo '{"status":"false","message":"Something went wrong while share"}';
				die;
			}

			$loguserid = $log_data['userid'];
			$sourceid = $log_data['sourceid'];
			$notifymessage = $log_data['notifymessage'];
			$message = $log_data['message'];
			$image = $log_data['image'];
			$shared = $log_data['shared'];
			$shareduserid = $log_data['shareduserid'];
			$logtype = $log_data['type'];
			$log = $this->Logs->newEntity();

			$log->type = $logtype;
			$log->userid = $userid;
			$log->sourceid = $sourceid;
			$log->itemid = 0;
			$log->notifyto = 0;
			$log->notifymessage = $notifymessage;
			$log->notification_id = 0;
			$log->message = $message;
			$log->image = $image;
			$log->cdate = time();
			if (!empty($shared) && $shared != '0') {
				$log->shared = $shared;
				$log->shareduserid = $shareduserid;
			} else {
				$log->shared = $logid;
				$log->shareduserid = $loguserid;
			}
			$this->Logs->save($log);
			$loguser = $this->Users->find()->where(['id' => $userid])->first();
			$followerstable = TableRegistry::get('Followers');
			$storefollowerstable = TableRegistry::get('Storefollowers');
			$flwrscnt = $followerstable->flwrscnt($userid);
			$flwrusrids = array();
			if (!empty($flwrscnt)) {
				foreach ($flwrscnt as $flwss) {
					$flwrusrids[$flwss['follow_user_id']] = $flwss['follow_user_id'];
				}
			}

			$storeflwrscnt = $storefollowerstable->flwrscnt($users['shop']['id']);
			$storeflwrusrids = array();
			if (!empty($storeflwrscnt)) {
				foreach ($storeflwrscnt as $storeflwss) {
					$storeflwrusrids[$storeflwss['follow_user_id']] = $storeflwss['follow_user_id'];
				}
			}
			$flwssuserids = array_merge($storeflwrusrids, $flwrusrids);
			foreach ($flwssuserids as $flwww) {
				$useriddd = $flwww;
				$userdevicestable = TableRegistry::get('Userdevices');
				$userddett = $userdevicestable->find('all')->where(['user_id' => $useriddd])->all();
				if (!empty($userddett)) {
					foreach ($userddett as $userdet) {
						$deviceTToken = $userdet['deviceToken'];
						$badge = $userdet['badge'];
						$badge += 1;

						$querys = $userdevicestable->query();
						$querys->update()
						->set(['badge' => $badge])
						->where(['deviceToken' => $deviceTToken])
						->execute();
						if (isset($deviceTToken)) {
							$pushMessage['type'] = 'share_status';
							$pushMessage['user_id'] = $loguser['id'];
							$pushMessage['feed_id'] = $logid;
							$pushMessage['user_name'] = $loguser['username'];
							$pushMessage['user_image'] = $userImg;
							$user_detail = TableRegistry::get('Users')->find()->where(['id' => $useriddd])->first();
							I18n::locale($user_detail['languagecode']);
							$pushMessage['message'] = __d('user', "shared the status");
							$messages = json_encode($pushMessage);
							$this->pushnot($deviceTToken, $messages, $badge);
						}
					}
				}
			}

			echo '{"status":"true","message":"Post is shared on your Feeds"}';
			die;
		} else {
			echo '{"status":"false","message":"Something went wrong while share"}';
			die;
		}
	}
	function deletepost()
	{

		$logusrid = $_POST['user_id'];

		$logId = $_POST['feed_id'];
			//$this->layout = "ajax";
		$this->loadModel('Logs');
		$this->loadModel('Followers');
		$this->loadModel('Users');
		$this->loadModel('Comments');
		$this->loadModel('Feedcomments');
		$this->loadModel('Hashtags');

		if (!empty($logId) && !empty($logusrid)) {

			$logModel = $this->Logs->find()->where(['id' => $logId])->first();//Byid($logId);
			$logimage = json_decode($logModel['image'], true);
			if (isset($logimage['status'])) {
				unlink(WEBROOT_PATH . 'media/status/original/' . $logimage['status']['image']);
				unlink(WEBROOT_PATH . 'media/status/thumb70/' . $logimage['status']['image']);
				unlink(WEBROOT_PATH . 'media/status/thumb150/' . $logimage['status']['image']);
				unlink(WEBROOT_PATH . 'media/status/thumb350/' . $logimage['status']['image']);
			}

			$logdata = $this->Logs->find()->where(['id' => $logId])->first();

			$comment = $logdata['message'];

			$usedHashtag = '';
			$oldHashtags = array();
			preg_match_all('/#([\S]*?)(?=\s)/', $comment, $hashmatch);

			if (!empty($hashmatch)) {
				foreach ($hashmatch[1] as $hashtag) {
					$cleanedHashtag = preg_replace('/[^A-Za-z0-9\-]/', '', $hashtag);
					if (!empty($cleanedHashtag) && $cleanedHashtag != '') {
						$hashtag = $cleanedHashtag;
						if ($usedHashtag == '') {
							$usedHashtag = $hashtag;
						}
						$usedHashtag .= ',' . $hashtag;

					}
				}
				$hashTags = explode(',', $usedHashtag);
				$hashtagsModel = $this->Hashtags->find()->where(['hashtag IN' => $hashTags])->all();//all',array(
				if (!empty($hashtagsModel)) {
					foreach ($hashtagsModel as $hashtags) {
						$id = $hashtags['id'];
						$count = $hashtags['usedcount'] - 1;
						$this->Hashtags->updateAll(array('usedcount' => $count), array('id' => $id));
						$oldHashtags[] = $hashtags['hashtag'];
					}
				}
			}
			$commentId = $logModel['sourceid'];
			$this->Comments->deleteAll(array('id' => $commentId));

			$this->Logs->deleteAll(array('id' => $logId));

			$feedId = array();
			$itemId = '-1';
				//$prefix = $this->Feedcomments->tablePrefix;
			$feedDetails = $this->Feedcomments->find()->where(['statusid' => $logId])->all();//all',array('Feedcomments.statusid' => $logId));
			if (count($feedDetails) != 0) {
				foreach ($feedDetails as $feedDetail) {
					$feedId[] = $feedDetail['id'];
					$fId = $feedDetail['id'];
					$this->Logs->deleteAll(['id' => $fId, 'itemid' => -1]);
							//$this->Log->query("DELETE FROM ".$prefix."logs WHERE id = $fId  AND itemid = $itemId");
				}
				$this->Feedcomments->deleteAll(array('statusid' => $logId));
				$this->Logs->deleteAll(['sourceid IN' => $feedId, 'itemid' => -1]);
			}

			echo '{"status":"true","message":"Your post has been deleted successfully"}';
			die;
		} else {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		}
	}

	function pushnotifications()
	{
		$userid = $_POST['user_id'];
		$offset = 0;
		$limit = 10;
		if (!empty($_POST['offset'])) {
			$offset = $_POST['offset'];
		}
		if (!empty($_POST['limit'])) {
			$limit = $_POST['limit'];
		}
		$status = $_POST['type'];
		$this->loadModel('Items');
		$this->loadModel('Comments');
		$this->loadModel('Itemfavs');
		$this->loadModel('Followers');
		$this->loadModel('Logs');
		$this->loadModel('Shops');
		$this->loadModel('Users');
		$this->loadModel('Likedusers');
		$this->loadModel('Photos');
		$this->loadModel('Storefollowers');
		$this->loadModel('Feedcomments');
		$this->loadModel('Disputes');
		$flwrscnt = $this->Followers->find()->where(['follow_user_id' => $userid])->all();//AllByfollow_user_id($userid);
		foreach ($flwrscnt as $flwr) {
			$flwruserid[] = $flwr['user_id'];
		}
		$storeflwrscnt = $this->Storefollowers->find()->where(['follow_user_id' => $userid])->all();//findAllByfollow_user_id($userid);
		foreach ($storeflwrscnt as $storeflwr) {
			$flwshopid = $storeflwr['store_id'];

			$shopModel = $this->Shops->find()->where(['id' => $flwshopid])->first();//ById($flwshopid);
			$storeflwruserid[] = $shopModel['user_id'];
		}
		if (empty($flwruserid)) {
			$flwruserid = array();
		}
		if (empty($storeflwruserid)) {
			$storeflwruserid = array();
		}
		$flwruserid = array_merge($storeflwruserid, $flwruserid);

		//echo '<pre>'; print_r($flwruserid); die;

		$userModel = $this->Users->find()->where(['id' => $userid])->first();
		$usercreateddate = strtotime($userModel['created_at']);
		if ($status == 'feeds') {
			$userModel->unread_livefeed_cnt = 0;
		}
		else{
			$userModel->unread_notify_cnt = 0;
		}
		$this->Users->save($userModel);
		$notificationSettings = json_decode($userModel['push_notifications'], true);
		if ($notificationSettings['somone_cmnts_push'] == 1) {
			$itemfav = $this->Itemfavs->find('all')->where(['user_id' => $userid])->all();
			foreach ($itemfav as $fav) {
				$itemfavid[] = $fav->item_id;
			}
		}
		$followType = array();
		$followType[] = 'additem';
		$followType[] = 'sellermessage';
		if ($notificationSettings['frends_cmnts_push'] == 0) {
			$followType = array();
			$followType[] = 'additem';
			$followType[] = 'comment';
			$followType[] = 'sellermessage';
		}

		$addedItems = array();
		if ($notificationSettings['frends_flw_push'] == 1) {
			$addedItems['userid IN'] = $flwruserid;
			$addedItems['type'] = 'additem';
		}
		if ($status == 'feeds') {
			$typeAs[] = 'follow';
			$typeAs[] = 'review';
			$typeAs[] = 'groupgift';
			$typeAs[] = 'sellermessage';
			$typeAs[] = 'admin';
			$typeAs[] = 'dispute';
			$typeAs[] = 'orderstatus';
			$typeAs[] = 'ordermessage';
			$typeAs[] = 'itemapprove';
			$typeAs[] = 'chatmessage';
			$typeAs[] = 'invite';
			$typeAs[] = 'credit';
			$typeAs[] = 'cartnotification';
			$typeConditions['type NOT IN'] = $typeAs;
		} else {
			$typeAs[] = 'comment';
			$typeAs[] = 'status';
			$typeAs[] = 'additem';
			$typeAs[] = 'favorite';
			$typeAs[] = 'checkin';
			$typeAs[] = 'mentioned';
			$typeConditions['type NOT IN'] = $typeAs;
		}

        // LOG NOTIFICATIONS
		$logstable = TableRegistry::get('Logs');
		$query = $logstable->query();

		if (!empty($flwruserid) && !empty($itemfavid)) {
			$userlogd = $query->where(['OR' =>
				[
					['userid IN' => $flwruserid, 'type NOT IN' => $followType, 'notifyto' => 0],
					['itemid IN' => $itemfavid, 'type' => 'comment'],
					['notifyto' => $userid],
					[$addedItems],
					['type' => 'admin', 'notifyto' => 0],
					['userid' => $userid, 'type' => 'status']
				]])->where($typeConditions)->where(['cdate >' => $usercreateddate])->order(['id DESC'])->limit($limit)->offset($offset)->all();

			

		} elseif (!empty($flwruserid)) {
			$userlogd = $query->where(['OR' =>
				[
					['userid IN' => $flwruserid, 'type NOT IN' => $followType, 'notifyto' => 0],
					['notifyto' => $userid],
					[$addedItems],
					['type' => 'admin', 'notifyto' => 0],
					['userid' => $userid, 'type' => 'status']
				]])->where($typeConditions)->where(['cdate >' => $usercreateddate])->order(['id DESC'])->limit($limit)->offset($offset)->all();

		} elseif (!empty($itemfavid)) {
			$userlogd = $query->where(['OR' =>
				[
					['notifyto' => $userid],
					['itemid IN' => $itemfavid, 'type' => 'comment'],
					['type' => 'admin', 'notifyto' => 0],
					['userid' => $userid, 'type' => 'status']
				]])->where($typeConditions)->where(['cdate >' => $usercreateddate])->order(['id DESC'])->limit($limit)->offset($offset)->all();

		} else {
			$userlogd = $query->where(['OR' =>
				[
					['notifyto' => $userid],
					['type' => 'admin', 'notifyto' => 0],
					['userid' => $userid, 'type' => 'status']
				]])->where($typeConditions)->where(['cdate >' => $usercreateddate])->order(['id DESC'])->limit($limit)->offset($offset)->all();

			
		}

		//echo '<pre>'; print_r($userlogd); die;


		$decoded_value = json_decode($userModel['push_notifications']);

        // Recent Activity
		$recentactivityType = array('comment', 'orderstatus', 'status', 'sellermessage');
		$recentactivity = $logstable->find('all')->where(['userid' => $userid])->where(['type IN' => $recentactivityType])->where(['cdate >' => $usercreateddate])->order(['id DESC'])->limit('5')->all();

		$resultArray['notifications'] = array();
		$key = 0;
		//print_r($userlogd); die;
		foreach ($userlogd as $log) {

			$resultArray['notifications'][$key]['self_check'] = $log['type'].' - '.$key;

			$not_type[$log['id']] = $log['type'];
			$notific_id[$log['id']] = $log['notification_id'];
				//$Log_cdate[$log['Log']['id']] = $log['Log']['cdate'];
			if (SITE_URL == $_SESSION['media_url']) {
				$img_path = trim($_SESSION['media_url']);
			} else {
				$img_path = trim($_SESSION['media_url']);
			}

			if ($log['type'] == 'comment') {
				$getLogvalues = $this->Comments->find()->where(['id' => $log['sourceid']])->first();//ById($log['Log']['sourceid']);
				if (count($getLogvalues['id']) != 0) {
					$resultArray['notifications'][$key]['type'] = 'comment';
					$resultArray['notifications'][$key]['user_id'] = $getLogvalues['user_id'];
					$userdet = $this->Users->find()->where(['id' => $getLogvalues['user_id']])->first();
					$resultArray['notifications'][$key]['user_name'] = $userdet['username'];
					$profile_image = $userdet['profile_image'];
					if (!empty($profile_image)) {
						$user_img = $profile_image;
					} else {
						$user_img = 'usrimg.jpg';
					}
					$resultArray['notifications'][$key]['user_image'] = $img_path . 'media/avatars/thumb70/' . $user_img;

					$resultArray['notifications'][$key]['item_id'] = $getLogvalues['item_id'];
					$itemdet = $this->Items->find()->where(['id' => $getLogvalues['item_id']])->first();
					$resultArray['notifications'][$key]['item_name'] = $itemdet['item_title'];
					$fileName = $this->Photos->find()->where(['item_id' => $itemdet['id']])->first();
					if ($getLogvalues['item_id'] == $fileName['item_id']) {
						if ($fileName['image_name'] != '') {
							$resultArray['notifications'][$key]['item_image'] = $img_path . 'media/items/thumb350/' . $fileName['image_name'];
						} else {
							$resultArray['notifications'][$key]['item_image'] = $img_path . 'media/items/thumb350/usrimg.jpg';
						}
					}
					$image = $resultArray['notifications'][$key]['item_image'];
					list($width, $height) = getimagesize($image);
					$resultArray['notifications'][$key]['height'] = $height;
					$resultArray['notifications'][$key]['width'] = $width;
					$resultArray['notifications'][$key]['comments'] = $getLogvalues['comments'];
					$resultArray['notifications'][$key]['date'] = $log['Log']['cdate'];
					$notifymsg = explode('-___-', $log['Log']['notifymessage']);	
					$key++;
				}

			}

			if ($log['type'] == 'additem') {
				$getLogvalues = $this->Items->find()->where(['id' => $log['itemid']])->first();//ById($log['Log']['itemid']);
				if (!empty($getLogvalues['id'])) {
					$resultArray['notifications'][$key]['type'] = 'add_item';
					$resultArray['notifications'][$key]['user_id'] = $getLogvalues['id'];
					$item_data = $this->Items->find()->where(['id' => $log['itemid']])->first();
					$shop_data = $this->Shops->find()->where(['id' => $item_data['shop_id']])->first();
					$resultArray['notifications'][$key]['store_id'] = $shop_data['id'];
					$resultArray['notifications'][$key]['store_name'] = $shop_data['shop_name'];
					$shop_image = $shop_data['shop_image'];
					if (!empty($shop_image)) {
						$user_img = $shop_image;
					} else {
						$user_img = 'usrimg.jpg';
					}
					$resultArray['notifications'][$key]['store_image'] = $img_path . 'media/avatars/thumb70/' . $user_img;
					$resultArray['notifications'][$key]['item_id'] = $log['itemid'];

					$resultArray['notifications'][$key]['item_name'] = $item_data['item_title'];

					$fileName = $this->Photos->find()->where(['item_id' => $log['itemid']])->first();//first',array('conditions'=>array('item_id'=>$getLogvalues['Item']['id'])));
					if ($fileName['image_name'] != '') {
						$resultArray['notifications'][$key]['item_image'] = $img_path . 'media/items/thumb350/' . $fileName['image_name'];
					} else {
						$resultArray['notifications'][$key]['item_image'] = $img_path . 'media/items/thumb350/usrimg.jpg';
					}

					$image = $resultArray['notifications'][$key]['item_image'];
					list($width, $height) = getimagesize($image);
					$resultArray['notifications'][$key]['height'] = $height;
					$resultArray['notifications'][$key]['width'] = $width;
					$resultArray['notifications'][$key]['date'] = $log['cdate'];

					$notifymsg = explode('-___-', $log['notifymessage']);
					$key++;

				}

			}

			if ($log['type'] == 'status' && $log['shared'] == "") {
				$getLogvalues = $this->Users->find()->where(['id' => $log['userid']])->first();
				$resultArray['notifications'][$key]['type'] = 'status';
				$resultArray['notifications'][$key]['feed_id'] = $log['id'];
				$resultArray['notifications'][$key]['message'] = $log['message'];
				$status_values = json_decode($log['image'], true);
				$status_image = $status_values['status']['image'];

				if ($status_image)
					$resultArray['notifications'][$key]['status_image'] = $img_path . 'media/status/original/' . $status_image;
				else
					$resultArray['notifications'][$key]['status_image'] = "";

				$image = $status_image;
				list($width, $height) = getimagesize($image);
				if (empty($height))
					$height = "350";
				if (empty($width))
					$width = "350";
				$resultArray['notifications'][$key]['height'] = $height;
				$resultArray['notifications'][$key]['width'] = $width;

				$logid = $log['id'];
				$feedfollowers = $this->Likedusers->find()->where(['statusid' => $logid])->all();
				$followinguserids = array();
				foreach ($feedfollowers as $ffollowers) {
					$followinguserids[] = $ffollowers['userid'];
				}
				if (in_array($userid, $followinguserids)) {
					$resultArray['notifications'][$key]['liked'] = "yes";
				} else {
					$resultArray['notifications'][$key]['liked'] = "no";
				}

				$resultArray['notifications'][$key]['likes_count'] = $log['likecount'];
				$resultArray['notifications'][$key]['comments_count'] = $log['commentcount'];

				$resultArray['notifications'][$key]['user_id'] = $getLogvalues['id'];

				$resultArray['notifications'][$key]['user_name'] = $getLogvalues['username'];
				$profile_image = $getLogvalues['profile_image'];
				if (!empty($profile_image)) {
					$user_img = $profile_image;
				} else {
					$user_img = 'usrimg.jpg';
				}

				$resultArray['notifications'][$key]['user_image'] = $img_path . 'media/avatars/thumb70/' . $user_img;
				$resultArray['notifications'][$key]['date'] = $log['cdate'];
				$notifymsg = explode('-___-', $log['notifymessage']);
				$key++;
			}

			if ($log['type'] == 'favorite') {

				$notificationImages = json_decode($log['image'], true);
				$getLogvalues = $this->Users->find()->where(['id' => $log['userid']])->first();
				$itemvalues = $this->Items->find()->where(['id' => $log['itemid']])->first();
				$resultArray['notifications'][$key]['type'] = 'liked';
				$resultArray['notifications'][$key]['user_id'] = $log['userid'];
				$resultArray['notifications'][$key]['user_name'] = $getLogvalues['username'];
				$resultArray['notifications'][$key]['message'] = $log['message'];
				$profile_image = $getLogvalues['profile_image'];
				if (!empty($profile_image)) {
					$user_img = $profile_image;
				} else {
					$user_img = 'usrimg.jpg';
				}
				$resultArray['notifications'][$key]['user_image'] = $img_path . 'media/avatars/thumb70/' . $user_img;
				$resultArray['notifications'][$key]['item_id'] = $itemvalues['id'];

				$resultArray['notifications'][$key]['item_name'] = $itemvalues['item_title'];

				if ($notificationImages['status']['image'] != '') {
					$resultArray['notifications'][$key]['item_image'] = $img_path . 'media/status/thumb350/' . $notificationImages['status']['image'];
				} else {
					$resultArray['notifications'][$key]['item_image'] = "";
				}
				$image = $resultArray['notifications'][$key]['item_image'];
				list($width, $height) = getimagesize($image);
				$resultArray['notifications'][$key]['height'] = $height;
				$resultArray['notifications'][$key]['width'] = $width;
				$resultArray['notifications'][$key]['date'] = $log['cdate'];
				$notifymsg = explode('-___-', $log['Log']['notifymessage']);
				$key++;
			}

			if ($log['type'] == 'checkin') 
			{
				$getLogvalues = $this->Users->find()->where(['id' => $log['userid']])->first();
				$resultArray['notifications'][$key]['type'] = 'checkin';
				$resultArray['notifications'][$key]['feed_id'] = $log['id'];
				$resultArray['notifications'][$key]['message'] = $log['message'];

				$logid = $log['id'];
				$feedfollowers = $this->Likedusers->find()->where(['statusid' => $logid])->all();
				$followinguserids = array();
				foreach ($feedfollowers as $ffollowers) {
					$followinguserids[] = $ffollowers['userid'];
				}
				if (in_array($userid, $followinguserids)) {
					$resultArray['notifications'][$key]['liked'] = "yes";
				} else {
					$resultArray['notifications'][$key]['liked'] = "no";
				}
				$resultArray['notifications'][$key]['likes_count'] = $log['likecount'];
				$resultArray['notifications'][$key]['comments_count'] = $log['commentcount'];

				$resultArray['notifications'][$key]['user_id'] = $getLogvalues['id'];

				$resultArray['notifications'][$key]['user_name'] = $getLogvalues['username'];
				$profile_image = $getLogvalues['profile_image'];
				if (!empty($profile_image)) {
					$user_img = $profile_image;
				} else {
					$user_img = 'usrimg.jpg';
				}
				$resultArray['notifications'][$key]['user_image'] = $img_path . 'media/avatars/thumb70/' . $user_img;
				$storeId = $log['sourceid'];
				$shop_datas = $this->Shops->find()->where(['id' => $storeId])->first();
				if ($shop_datas['shop_image'] == "")
					$shopimage = "usrimg.jpg";
				else
					$shopimage = $shop_datas['shop_image'];
				$image = $img_path . 'media/status/thumb70/' . $shopimage;
				$resultArray['notifications'][$key]['store_id'] = $storeId;
				$resultArray['notifications'][$key]['store_name'] = $shop_datas['shop_name'];
				$resultArray['notifications'][$key]['store_image'] = $image;
				$resultArray['notifications'][$key]['date'] = $log['cdate'];

				$notifymsg = explode('-___-', $log['Log']['notifymessage']);
				$key++;
			}

			if ($log['type'] == 'status' && $log['shared'] != 0) {
				$getLogvalues = $this->Users->find()->where(['id' => $log['userid']])->first();
				$resultArray['notifications'][$key]['type'] = 'status_share';
				$resultArray['notifications'][$key]['feed_id'] = $log['id'];
				$resultArray['notifications'][$key]['message'] = $log['message'];
				$status_values = json_decode($log['image'], true);
				$status_image = $status_values['status']['image'];

				if ($status_image)
					$resultArray['notifications'][$key]['status_image'] = $img_path . 'media/status/original/' . $status_image;
				else
					$resultArray['notifications'][$key]['status_image'] = "";

				$image = $status_image;
				list($width, $height) = getimagesize($image);
				if (empty($height))
					$height = "350";
				if (empty($width))
					$width = "350";
				$resultArray['notifications'][$key]['height'] = $height;
				$resultArray['notifications'][$key]['width'] = $width;

				$logid = $log['id'];
				$feedfollowers = $this->Likedusers->find()->where(['statusid' => $logid])->all();//',array('conditions'=>array('Likedusers.statusid'=>$logid)));
							//echo count($feedfollowers);
				$followinguserids = array();
				foreach ($feedfollowers as $ffollowers) {
					$followinguserids[] = $ffollowers['userid'];
				}
				if (in_array($userid, $followinguserids)) {
					$resultArray['notifications'][$key]['liked'] = "yes";
				} else {
					$resultArray['notifications'][$key]['liked'] = "no";
				}

				$resultArray['notifications'][$key]['likes_count'] = $log['likecount'];
				$resultArray['notifications'][$key]['comments_count'] = $log['commentcount'];
				$shared = $log['shared'];
				$shareduserid = $log['shareduserid'];
				if (!empty($shared) && $shared != '0')
					$resultArray['notifications'][$key]['shared_feed_id'] = $shared;
				if (!empty($shareduserid) && $shareduserid != 0) {
					$resultArray['notifications'][$key]['shared_user_id'] = $shareduserid;
					$shareduserdatas = $this->Users->find()->where(['id' => $shareduserid])->first();//ById($shareduserid);
					$resultArray['notifications'][$key]['shared_user_name'] = $shareduserdatas['username'];
				}

				$resultArray['notifications'][$key]['user_id'] = $getLogvalues['id'];

				$resultArray['notifications'][$key]['user_name'] = $getLogvalues['username'];
				$profile_image = $getLogvalues['profile_image'];
				if (!empty($profile_image)) {
					$user_img = $profile_image;
				} else {
					$user_img = 'usrimg.jpg';
				}
				$resultArray['notifications'][$key]['user_image'] = $img_path . 'media/avatars/thumb70/' . $user_img;
				$resultArray['notifications'][$key]['date'] = $log['cdate'];
				$notifymsg = explode('-___-', $log['notifymessage']);
							//$resultArray['notifications'][$key]['notifymessage'] = $notifymsg;
				$key++;
			}

			if ($log['type'] == 'mentioned') {
				$feedImages = json_decode($log['image'], true);
				if (!empty($feedImages['item'])) {

					$getLogvalues = $this->Comments->find()->where(['id' => $log['sourceid']])->first();
					$user_data = $this->Users->find()->where(['id' => $getLogvalues['user_id']])->first();
					$item_data = $this->Items->find()->where(['id' => $getLogvalues['item_id']])->first();
					if (!empty($getLogvalues['item_id'])) {
						$resultArray['notifications'][$key]['type'] = 'mentioned';
						$resultArray['notifications'][$key]['user_id'] = $getLogvalues['user_id'];
						$resultArray['notifications'][$key]['user_name'] = $user_data['username'];
						$profile_image = $user_data['User']['profile_image'];
						if (!empty($profile_image)) {
							$user_img = $profile_image;
						} else {
							$user_img = 'usrimg.jpg';
						}
						$resultArray['notifications'][$key]['user_image'] = $img_path . 'media/avatars/thumb70/' . $user_img;

						$resultArray['notifications'][$key]['item_id'] = $getLogvalues['item_id'];
						$resultArray['notifications'][$key]['item_name'] = $item_data['item_title'];
						$fileName = $this->Photos->find()->where(['item_id' => $log['itemid']])->first();

						if ($fileName['image_name'] != '') {
							$resultArray['notifications'][$key]['item_image'] = $img_path . 'media/items/thumb350/' . $fileName['image_name'];
						} else {
							$resultArray['notifications'][$key]['item_image'] = $img_path . 'media/items/thumb350/usrimg.jpg';
						}

						$image = $resultArray['notifications'][$key]['item_image'];
						list($width, $height) = getimagesize($image);
						$resultArray['notifications'][$key]['height'] = $height;
						$resultArray['notifications'][$key]['width'] = $width;
						$resultArray['notifications'][$key]['date'] = $log['cdate'];
						$notifymsg = explode('-___-', $log['Log']['notifymessage']);
						 		//$resultArray['notifications'][$key]['notifymessage'] = $notifymsg;
						$key++;

					}

				} else {
					$getLogvalues = $this->Feedcomments->find()->where(['id' => $log['sourceid']])->first();
					$user_data = $this->Users->find()->where(['id' => $getLogvalues['userid']])->first();
					if (!empty($getLogvalues['id'])) {
						$resultArray['notifications'][$key]['type'] = 'mentioned_status';
						$resultArray['notifications'][$key]['feed_id'] = $log['id'];
						$resultArray['notifications'][$key]['user_id'] = $getLogvalues['id'];

						$resultArray['notifications'][$key]['user_name'] = $user_data['username'];
						$profile_image = $user_data['User']['profile_image'];
						if (!empty($profile_image)) {
							$user_img = $profile_image;
						} else {
							$user_img = 'usrimg.jpg';
						}
						$resultArray['notifications'][$key]['user_image'] = $img_path . 'media/avatars/thumb70/' . $user_img;
						$resultArray['notifications'][$key]['comments'] = $getLogvalues['comments'];

						$resultArray['notifications'][$key]['date'] = $log['cdate'];
						$notifymsg = explode('-___-', $log['Log']['notifymessage']);
						$key++;
					}

				}

			}

			if ($log['type'] == 'follow') {
				$getLogvalues = $this->Users->find()->where(['id' => $log['userid']])->first();
				$resultArray['notifications'][$key]['type'] = 'follow';
				$resultArray['notifications'][$key]['user_id'] = $getLogvalues['id'];
				$resultArray['notifications'][$key]['user_name'] = $getLogvalues['username'];
				$profile_image = $getLogvalues['profile_image'];
				if (!empty($profile_image)) {
					$user_img = $profile_image;
				} else {
					$user_img = 'usrimg.jpg';
				}
				$resultArray['notifications'][$key]['user_image'] = $img_path . 'media/avatars/thumb70/' . $user_img;
				$resultArray['notifications'][$key]['date'] = $log['cdate'];
				$notifymsg = explode('-___-', $log['notifymessage']);
				$key++;
			}

			if ($log['type'] == 'orderstatus') {
				$getLogvalues = $this->Users->find()->where(['id' => $log['userid']])->first();
				$resultArray['notifications'][$key]['type'] = 'order_status';
				$resultArray['notifications'][$key]['user_id'] = $getLogvalues['id'];
				$resultArray['notifications'][$key]['order_id'] = $log['sourceid'];
				$resultArray['notifications'][$key]['user_name'] = $getLogvalues['username'];
				$profile_image = $getLogvalues['profile_image'];
				if (!empty($profile_image)) {
					$user_img = $profile_image;
				} else {
					$user_img = 'usrimg.jpg';
				}
				$resultArray['notifications'][$key]['user_image'] = $img_path . 'media/avatars/thumb70/' . $user_img;
				$notifymsg = explode('-___-', $log['notifymessage']);
				$resultArray['notifications'][$key]['order_message'] = $notifymsg[0];
				$resultArray['notifications'][$key]['date'] = $log['cdate'];					
				$key++;

			}

			if ($log['type'] == 'review') {
				$getLogvalues = $this->Users->find()->where(['id' => $log['userid']])->first();
				$resultArray['notifications'][$key]['type'] = 'review';
				$resultArray['notifications'][$key]['user_id'] = $getLogvalues['id'];
				$resultArray['notifications'][$key]['user_name'] = $getLogvalues['username'];

				$profile_image = $getLogvalues['profile_image'];
				if (!empty($profile_image)) {
					$user_img = $profile_image;
				} else {
					$user_img = 'usrimg.jpg';
				}
				$resultArray['notifications'][$key]['user_image'] = $img_path . 'media/avatars/thumb70/' . $user_img;
				$resultArray['notifications'][$key]['review'] = $log['message'];
				$resultArray['notifications'][$key]['date'] = $log['cdate'];
				$key++;

			}
			if ($log['type'] == 'cartnotification') { //echo"<pre>";print_r($log);die;
							//echo "<br />add".$log['Log']['id'];
							//print_r($getLogvalues);
							//echo"<pre>";print_r($log);
			$resultArray['notifications'][$key]['type'] = 'cart_notification';
			$resultArray['notifications'][$key]['admin_image'] = $img_path . 'media/avatars/thumb70/usrimg.jpg';
			$resultArray['notifications'][$key]['date'] = $log['cdate'];
			$key++;
		}
		if ($log['type'] == 'sellermessage') {

			if ($log['notifyto'] != "0") {
				$resultArray['notifications'][$key]['type'] = 'seller_news';
				$shop_data = $this->Shops->find()->where(['user_id' => $log['userid']])->first();
				$resultArray['notifications'][$key]['store_id'] = $shop_data['id'];
				$resultArray['notifications'][$key]['store_name'] = $shop_data['shop_name'];
				$profile_image = $shop_data['shop_image'];
				if (!empty($profile_image)) {
					$user_img = $profile_image;
				} else {
					$user_img = 'usrimg.jpg';
				}
				$resultArray['notifications'][$key]['store_image'] = $img_path . 'media/avatars/thumb70/' . $user_img;
				$resultArray['notifications'][$key]['message'] = $log['message'];
				$resultArray['notifications'][$key]['date'] = $log['cdate'];
				$notifymsg = explode('-___-', $log['notifymessage']);
				$key++;
			}

		}

		if ($log['type'] == 'chatmessage') {

			$resultArray['notifications'][$key]['type'] = 'chat_message';
			$resultArray['notifications'][$key]['chat_id'] = $log['sourceid'];
			$item_data = $this->Items->find()->where(['id' => $log['itemid']])->first();
			$shop_data = $this->Shops->find()->where(['id' => $item_data['shop_id']])->first();
			$resultArray['notifications'][$key]['store_id'] = $shop_data['id'];
			$resultArray['notifications'][$key]['store_name'] = $shop_data['shop_name'];
			$profile_image = $shop_data['shop_image'];
			if (!empty($profile_image)) {
				$user_img = $profile_image;
			} else {
				$user_img = 'usrimg.jpg';
			}
			$resultArray['notifications'][$key]['store_image'] = $img_path . 'media/avatars/thumb70/' . $user_img;

			$resultArray['notifications'][$key]['message'] = $log['message'];
			$resultArray['notifications'][$key]['date'] = $log['cdate'];

			$key++;
		}
		if ($log['type'] == 'credit') {
			$resultArray['notifications'][$key]['type'] = 'credit';
			$resultArray['notifications'][$key]['user_image'] = $img_path . 'media/avatars/thumb70/usrimg.jpg';

			$resultArray['notifications'][$key]['message'] = $log['message'];
			$notifymsg = explode('-___-', $log['notifymessage']);
			$resultArray['notifications'][$key]['date'] = $log['cdate'];
			$key++;
		}
		if ($log['type'] == 'invite') {
			$getLogvalues = $this->Users->find()->where(['id' => $log['userid']])->first();
			$resultArray['notifications'][$key]['type'] = 'invite';
			$resultArray['notifications'][$key]['user_id'] = $getLogvalues['id'];
			$resultArray['notifications'][$key]['user_name'] = $getLogvalues['username'];

			$profile_image = $getLogvalues['profile_image'];
			if (!empty($profile_image)) {
				$user_img = $profile_image;
			} else {
				$user_img = 'usrimg.jpg';
			}
			$resultArray['notifications'][$key]['user_image'] = $img_path . 'media/avatars/thumb70/' . $user_img;

			$resultArray['notifications'][$key]['message'] = $getLogvalues['first_name'] . " accepted your invitation and joined. You can follow " . $getLogvalues['first_name'] . " by visiting the profile";
			$resultArray['notifications'][$key]['date'] = $log['cdate'];
			$notifymsg = explode('-___-', $log['Log']['notifymessage']);
			$key++;

		}
		if ($log['type'] == 'dispute') {

			$getLogvalues = $this->Users->find()->where(['id' => $log['userid']])->first();

			$resultArray['notifications'][$key]['type'] = 'dispute_message';
			$resultArray['notifications'][$key]['dispute_id'] = $log['sourceid'];
			$disp_data = $this->Disputes->find()->where(['disid' => $log['sourceid']])->first();
			$shop_data = $this->Shops->find()->where(['user_id' => $disp_data['selid']])->first();
							//Dispute status
			$activeStatus = array('Reply', 'Initialized', 'Responded', 'Reopen');
			if (in_array($disp_data['newstatusup'], $activeStatus))
				$disputestatus = "Active";
			elseif ($disp_data['newstatusup'] == 'Accepeted')
				$disputestatus = "Accepeted";
			else
				$disputestatus = "Closed";
			$resultArray['notifications'][$key]['dispute_status'] = $disputestatus;
			$resultArray['notifications'][$key]['store_id'] = $shop_data['id'];
			$resultArray['notifications'][$key]['store_name'] = $shop_data['shop_name'];
			$profile_image = $shop_dat['shop_image'];
			if (!empty($profile_image)) {
				$user_img = $profile_image;
			} else {
				$user_img = 'usrimg.jpg';
			}
			$resultArray['notifications'][$key]['store_image'] = $img_path . 'media/avatars/thumb70/' . $user_img;

			$resultArray['notifications'][$key]['message'] = $log['message'];
			$resultArray['notifications'][$key]['date'] = $log['cdate'];
			$key++;
		}

		if ($log['type'] == 'admin') {
			if (!empty($log['message'])) {
				$resultArray['notifications'][$key]['type'] = 'admin';
				$itemvalues = $this->Items->find()->where(['id' => $log['sourceid']])->first();
				$photos = $this->Photos->find()->where(['item_id' => $log['sourceid']])->first();
				$resultArray['notifications'][$key]['admin_type'] = 'news';
				if (!empty($log['message']))
					$resultArray['notifications'][$key]['message'] = $log['message'];
				else
					$resultArray['notifications'][$key]['message'] = '';

				$resultArray['notifications'][$key]['item_id'] = $log['sourceid'];
				$resultArray['notifications'][$key]['item_name'] = $itemvalues['item_title'];
				if ($photos['image_name'] != '') {
					$resultArray['notifications'][$key]['item_image'] = $img_path . 'media/items/thumb350/' . $fileName['image_name'];
					$image = $resultArray['notifications'][$key]['item_image'];
					list($width, $height) = getimagesize($image);
				} else {
					$resultArray['notifications'][$key]['item_image'] = "";
					$height = "";
					$width = "";
				}

				$resultArray['notifications'][$key]['height'] = $height;
				$resultArray['notifications'][$key]['width'] = $width;
				$resultArray['notifications'][$key]['admin_image'] = $img_path . 'media/avatars/thumb70/usrimg.jpg';
				$resultArray['notifications'][$key]['date'] = $log['cdate'];
				$notifymsg = explode('-___-', $log['notifymessage']);
				$key++;
			}
		}

		if ($log['type'] == 'groupgift') {
			$getLogvalues = $this->Users->find()->where(['id' => $log['userid']])->first();
			$resultArray['notifications'][$key]['type'] = 'group_gift';
			$resultArray['notifications'][$key]['user_id'] = $getLogvalues['id'];
			$resultArray['notifications'][$key]['user_name'] = $getLogvalues['username'];
			$profile_image = $getLogvalues['profile_image'];
			if (!empty($profile_image)) {
				$user_img = $profile_image;
			} else {
				$user_img = 'usrimg.jpg';
			}
			$resultArray['notifications'][$key]['user_image'] = $img_path . 'media/avatars/thumb70/' . $user_img;
			$notifymsg = explode('-___-', $log['notifymessage']);
			$resultArray['notifications'][$key]['message'] = $notifymsg[0].$log['sourceid'];
			$resultArray['notifications'][$key]['date'] = $log['cdate'];
			$key++;
		}

	}

	if (empty($resultArray['notifications'])) {
		echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
		die;
	} else {
		echo '{"status":"true","result":' . json_encode($resultArray['notifications']) . '}';
		die;
	}

}

function activeDisp()
{

	$this->loadModel('Disputes');
	$this->loadModel('Dispcons');
	$this->loadModel('Items');
	$this->loadModel('Shops');
	$this->loadModel('Photos');
	$this->loadModel('Users');
	$this->loadModel('Sitesettings');
	$this->loadModel('Order_items');

	$timeline = strtotime('-10 day');
	$userId = $_POST['user_id'];

	$userDetail = $this->Users->find()->where(['id' => $userId])->first();

	$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();
	$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();

	if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "") {
		$cur_symbol = $forexrateModel['currency_symbol'];
		$cur = $forexrateModel['price'];
	} else {
		$cur_symbol = $currency_value['currency_symbol'];
		$cur = $currency_value['price'];
	}
	$setngs = $this->Sitesettings->find()->toArray();
	if (SITE_URL == $setngs[0]['media_url']) {
		$img_path = $setngs[0]['media_url'];
	} else {
		$img_path = $setngs[0]['media_url'];
	}
	if (isset($_POST['limit'])) {
		$limit = $_POST['limit'];
	} else {
		$limit = 10;
	}
	$status = array('Reply' => 'Reply', 'Initialized' => 'Initialized', 'Reopen' => 'Reopen', 'Responded' => 'Responded', 'Accepeted' => 'Accepeted');

	if (isset($_POST['offset'])) {
		$activedisp_data = $this->Disputes->find('all', array(
			'conditions' => array(
				'newstatusup IN' => $status,
				'chatdate >' => $timeline,
				'userid' => $userId
			),
			'limit' => $limit,
			'offset' => $_POST['offset'],
			'order' => 'disid DESC',
		));

	} else {
		$activedisp_data = $this->Disputes->find('all', array(
			'conditions' => array(
				'newstatusup IN' => $status,
				'chatdate >' => $timeline,
				'userid' => $userId
			),
			'limit' => $limit,
			'order' => 'disid DESC',
		));
	}
	$resultarray = array();
	foreach ($activedisp_data as $key => $activedisp_datas) {

		$resultarray[$key]['dispute_id'] = $activedisp_datas['disid'];

		if ($activedisp_datas['newstatus'] == 'Accepeted' && $activedisp_datas['newstatusup'] != 'Resolved') {
			$resultarray[$key]['type'] = 'Accepted';
		} else {
			$resultarray[$key]['type'] = 'Active';
		}
		if ($activedisp_datas['newstatusup'] == 'Accepeted')
			$resultarray[$key]['status'] = 'Accepted';
		else
			$resultarray[$key]['status'] = $activedisp_datas['newstatusup'];
		$resultarray[$key]['currency'] = $cur_symbol;
		if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "")
			$resultarray[$key]['price'] = $activedisp_datas['totprice'];
		else
			$resultarray[$key]['price'] = $activedisp_datas['totprice'];
		$resultarray[$key]['dispute_date'] = $activedisp_datas['chatdate'];
		$order_data = $this->Order_items->find()->where(['orderid' => $activedisp_datas['uorderid']])->first();
		$itemId = $order_data['itemid'];
		$item_data = $this->Items->find()->where(['id' => $itemId])->first();
		$resultarray[$key]['item_title'] = $item_data['item_title'];
		$resultarray[$key]['item_id'] = $item_data['id'];
		$photo = $this->Photos->find()->where(['item_id' => $item_data['id']])->first();
		if ($photo['image_name'] == "") {
			$itemImage = "usrimg.jpg";
		} else {
			$itemImage = $photo['image_name'];
		}

		$resultarray[$key]['image'] = $img_path . 'media/items/thumb350/' . $itemImage;
		$shop_data = $this->Shops->find()->where(['id' => $item_data['shop_id']])->first();
		$resultarray[$key]['shop_id'] = $shop_data['id'];
		$resultarray[$key]['shop_name'] = $shop_data['shop_name'];

		if ($shop_data['shop_image'] == "") {
			$shopImage = "usrimg.jpg";
		} else {
			$shopImage = $shop_data['shop_image'];

		}
		$resultarray[$key]['shop_image'] = $img_path . 'media/avatars/thumb350/' . $shopImage;
		$activedisp_cons = $this->Dispcons->find('all', array(
			'conditions' => array(
				'dispid' => $activedisp_datas['disid'],
			),
			'limit' => 1,
			'order' => 'dcid DESC',
		));
		foreach ($activedisp_cons as $cons) {
			if ($cons['commented_by'] == "Seller")
				$resultarray[$key]['last_replied'] = $activedisp_datas['selid'];
			else
				$resultarray[$key]['last_replied'] = $activedisp_datas['userid'];

		}

	}
	if (!empty($resultarray)) {

		echo '{"status": "true", "result": ' . json_encode($resultarray) . '}';
		die;

	} else {
		echo '{"status": "false", "message": "No data found"}';
		die;

	}

}

function disputeChat()
{

	$this->loadModel('Disputes');
	$this->loadModel('Dispcons');
	$this->loadModel('Items');
	$this->loadModel('Shops');
	$this->loadModel('Photos');
	$this->loadModel('Users');
	$this->loadModel('Sitesettings');
	$this->loadModel('Order_items');

	$timeline = strtotime('-10 day');
	$userId = $_POST['user_id'];

	$dispid = $_POST['dispute_id'];
	$userDetail = $this->Users->find()->where(['id' => $userId])->first();

	$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();
	$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();

	if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "") {
		$cur_symbol = $forexrateModel['currency_symbol'];

		$cur = $forexrateModel['price'];
	} else {
		$cur_symbol = $currency_value['currency_symbol'];
		$cur = $currency_value['price'];
	}
	$setngs = $this->Sitesettings->find()->toArray();
	if (SITE_URL == $setngs[0]['media_url']) {
		$img_path = $setngs[0]['media_url'];
	} else {
		$img_path = $setngs[0]['media_url'];
	}
	if (isset($_POST['limit'])) {
		$limit = $_POST['limit'];
	} else {
		$limit = 10;
	}
	if (isset($_POST['offset'])) {
		$offset = $_POST['offset'];
	} else {
		$offset = 0;
	}
			//$status = array('Reply' => 'Reply','Initialized'=>'Initialized','Reopen' => 'Reopen','Responded'=>'Responded','Accepted'=>'Accepted' );

	$activedisp_datas = $this->Disputes->find()->where(['disid' => $dispid])->first();
	if (count($activedisp_datas) == 0) {
		echo '{"status": "false", "message": "No data found"}';
		die;
	}

	$resultarray = array();

	$resultarray['dispute_id'] = $activedisp_datas['disid'];
	$resultarray['currency'] = $cur_symbol;
	if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "")
		$resultarray['price'] = $activedisp_datas['totprice'];
	else
		$resultarray['price'] = $activedisp_datas['totprice'];
	$resultarray['dispute_date'] = $activedisp_datas['chatdate'];
	$order_data = $this->Order_items->find()->where(['orderid' => $activedisp_datas['uorderid']])->first();
	$itemId = $order_data['itemid'];
	$item_data = $this->Items->find()->where(['id' => $itemId])->first();
	$resultarray['item_title'] = $item_data['item_title'];
	$resultarray['item_id'] = $item_data['id'];
	$photo = $this->Photos->find()->where(['item_id' => $item_data['id']])->first();
	if ($photo['image_name'] == "") {
		$itemImage = "usrimg.jpg";
	} else {
		$itemImage = $photo['image_name'];
	}

	$resultarray['image'] = $img_path . 'media/items/thumb350/' . $itemImage;
	$shop_data = $this->Shops->find()->where(['id' => $item_data['shop_id']])->first();
	$resultarray['shop_id'] = $shop_data['id'];
	$resultarray['shop_name'] = $shop_data['shop_name'];

	if ($shop_data['shop_image'] == "") {
		$shopImage = "usrimg.jpg";
	} else {
		$shopImage = $shop_data['shop_image'];

	}
	$resultarray['shop_image'] = $img_path . 'media/avatars/thumb350/' . $shopImage;
	$activedisp_cons = $this->Dispcons->find('all', array(
		'conditions' => array(
			'dispid' => $activedisp_datas['disid'],
		),
		'limit' => $limit,
		'offset' => $offset,
		'order' => 'dcid DESC',
	));

	$resultarray['messages'] = array();
	foreach ($activedisp_cons as $key => $cons) {
		if ($cons['imagedisputes'] != "") {
			$resultarray['messages'][$key]['type'] = "attachment";
			$resultarray['messages'][$key]['message'] = $cons['message'];
			$resultarray['messages'][$key]['attachment'] = $img_path . 'disputeimage/' . $cons['imagedisputes'];
		} else {
			$resultarray['messages'][$key]['type'] = "message";
			$resultarray['messages'][$key]['message'] = $cons['message'];
			$resultarray['messages'][$key]['attachment'] = "";
		}
					//$resultarray['messages'][$key]['message']=$cons['message'];
		if ($cons['commented_by'] == "Buyer")

			$user_id = $activedisp_datas['userid'];
		else
			$user_id = $activedisp_datas['selid'];
		$user_data = $this->Users->find()->where(['id' => $user_id])->first();

					//$resultarray['messages'][$key]['attachment']="";
		$resultarray['messages'][$key]['user_id'] = $user_id;
		$resultarray['messages'][$key]['user_name'] = $user_data['username'];
		$resultarray['messages'][$key]['full_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
		if ($user_data['profile_image'] == "")
			$userImage = "usrimg.jpg";
		else
			$userImage = $user_data['profile_image'];
		$resultarray['messages'][$key]['user_image'] = $img_path . 'media/avatars/thumb350/' . $userImage;
		$resultarray['messages'][$key]['chat_date'] = $cons['date'];

	}

	if (!empty($resultarray)) {

		echo '{"status": "true", "result": ' . json_encode($resultarray) . '}';
		die;

	} else {
		echo '{"status": "false", "message": "No data found"}';
		die;

	}

}

function dispute()
{

	$userid = $_POST['user_id'];

	$orderid = $_POST['order_id'];
	$title = $_POST['title'];
	$message = $_POST['message'];
	$itemid = $_POST['item_ids'];
			//$usr_datas = $this->Users->find()->where(['id'=>$userid])->first();//ById($loguser[0]['User']['id']);
			//$emailaddress = $usr_datas['email'];

	$this->loadModel('Orders');
	$this->loadModel('Order_items');
	$this->loadModel('Items');
	$this->loadModel('Users');
	$this->loadModel('Disputes');
	$this->loadModel('Dispcons');
	$this->loadModel('Sitesettings');
	$this->loadModel('Sitequeries');
	$this->loadModel('Countries');
	$this->loadModel('Shippingaddresses');
	$this->loadModel('Shipings');
	$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
	$subject_data = $this->Sitequeries->find()->where(['type' => 'Dispute_Problem'])->first();

		//	$orderModel = $this->Orders->find()->where(['orderid'=>$orderid])->first();//Byorderid($orderid);
		//	$merchantid = $orderModel['merchant_id'];
		//	$merchantModel = $this->Users->find()->where(['id'=>$merchantid])->first();//Byid($merchantid);

		$orderitemModel = $this->Order_items->find()->where(['orderid' => $orderid])->all();//all',array('conditions'=>array('orderid'=>$orderid)));

		if ($userid != "") {

			$or = $orderid;
			$orderModel = $this->Orders->find()->where(['orderid' => $orderid])->first();//Byorderid($orderid);
			if (count($orderModel) == 0) {

				echo '{"status": "false", "message": "Something went to be wrong,Please try again later."}';
				die;
			}
			$merchantid = $orderModel['merchant_id'];
			$resol = $orderModel['status'];
			$total = $orderModel['totalcost'];

			$ordate = $orderModel['orderdate'];
			$userModel = $this->Users->find()->where(['id' => $userid])->first();
			if (count($userModel) == 0) {

				echo '{"status": "false", "message": "Something went to be wrong,Please try again later."}';
				die;
			}
			$merchantModel = $this->Users->find()->where(['id' => $merchantid])->first();
			if (count($merchantModel) == 0) {

				echo '{"status": "false", "message": "Something went to be wrong,Please try again later."}';
				die;
			}
			$userEmail = $userModel['email'];
			$merEmail = $merchantModel['email'];
			$buyName = $userModel['first_name'];

			$userName = $userModel['first_name'] . ' ' . $userModel['last_name'];
			$merName = $merchantModel['first_name'] . ' ' . $merchantModel['last_name'];
			$merurlname = $userModel['username_url'];
			$orderlist = $this->Order_items->find()->where(['orderid' => $orderid])->first();//Byorderid($orderid);
			$oname = $orderlist['itemname'];
			$buyer_url = $userModel['username_url'];
			$seller_url = $merchantModel['username_url'];

			$disp_data = $this->Disputes->newEntity();
			$uids = $disp_data->userid = $userid;
			$sids = $disp_data->selid = $merchantid;
			$or = $disp_data->uorderid = $orderid;
			$una = $disp_data->uname = $userName;
			$uema = $disp_data->uemail = $userEmail;
			$sna = $disp_data->sname = $merName;
			$sema = $disp_data->semail = $merEmail;
			$plm = $disp_data->uorderplm = $title;
			$msg = $disp_data->uordermsg = $message;
			$ms = $disp_data->chatdate = time();

			if (!empty($_POST['item_ids'])) {
				$item_id = explode(",", $_POST['item_ids']);
				foreach ($item_id as $item_ids) {
					$itemdet[] = $item_ids;
				}

			}
				/*print_r($itenmaedet); die;
				$col=$itemid;

				$ful= json_encode($col);
				$diin=$itemid=$ful;
				$itenmaedet = json_decode($diin, true);*/

				$si = 0;
				$quanpri = 0;
				$sprice = 0;
				if (count($itemdet) != 0) {
					foreach ($itemdet as $key => $itemdets) {

						$itemModel = $this->Items->find()->where(['id' => $itemdets])->first();
						$ipr[] = $itemModel['price'];
					$orderitemdetails = $this->Order_items->find()->where(['itemid' => $itemdets])->andWhere(['orderid' => $orderid])->first();//irst',array('conditions'=>array('itemid'=>$tiemamou,'orderid'=>$orderid)));

					$pr = $orderitemdetails['itemunitprice'];
				 // echo "quan";
					$qu = $orderitemdetails['itemquantity'];	//echo "tot";
					$tot = $orderitemdetails['itemunitprice'] * $orderitemdetails['itemquantity'];
					$sipp = $orderitemdetails['shippingprice'];
					$si += $sipp;
					$quanpri += $tot;
					$orderitemdetails['shippingprice'];
					$amo = $si + $quanpri;

					$useraddr = $this->Orders->find()->where(['orderid' => $orderid])->first();//Byorderid($orderid);
					$addrs = $useraddr['shippingaddress'];

					$address = $this->Shippingaddresses->find()->where(['shippingid' => $addrs])->first();//Byshippingid($addrs);
					$cou = $address['country'];

					$coun = $this->Countries->find()->where(['country' => $cou])->first();//Bycountry($cou);
					$cou = $coun['id'];

					$shipprice = $this->Shipings->find()->where(['item_id' => $itemdets])->andWhere(['country_id' => $cou])->all();//',array('conditions'=>array('item_id'=>$tiemamou,'country_id'=>$cou)));

					foreach ($shipprice as $ship) {
						$shipri = $ship['primary_cost'];
						$sprice += $shipri;
					}
				}
			}

			$toshippedprice = $quanpri + $sprice;

			$disp_data->money = $merurlname;

			/*	if($this->request->data['Dispute']['types'] == 'Order'){
					$this->request->data['Dispute']['totprice']=$total;
				}else{
				$orname=$this->request->data['Dispute']['totprice']=$amo;
			}*/
			$nefirst = 'Initialized';
			$disp_data->newstatus = 'Initialized';
			$disp_data->newstatusup = 'Initialized';

			if ($itemid == "") {
				$disp_data->uorderstatus = 'null';
				$disp_data->itemdetail = 'null';
			} else {
				$disp_data->uorderstatus = json_encode($itemdet);
				$disp_data->itemdetail = json_encode($itemdet);
			}
			if ($itemid == "") {
				$disp_data->orderitem = 'Order';
				$disp_data->totprice = $total;
			} else {
				$disp_data->orderitem = 'Item';
				$orname = $disp_data->totprice = $amo;
			}

			$oda = $disp_data->orderdatedisp = $ordate;
			$neia = "Buyer";

				//$cre=$disp_data->create;
			$cre = 'Buyer';
			$resolved = "Pending";
			$disp_data->resolvestatus = 'Pending';

			$result = $this->Disputes->save($disp_data);

			$dis = $result->disid;

				// $query = $this->Disputes->query();
				 //$query->update()
			    //->set(['fc_disputes.create' => 'Buyer'])
			    //->where(['disid' => $dis])
			    //->execute();

			//	 $this->Disputes->query("UPDATE fc_disputes SET create ='Buyer' WHERE disid = 11");
			$dispcon_data = $this->Dispcons->newEntity();
			$cuids = $dispcon_data->user_id = $userid;
			$gli = $dispcon_data->dispid = $dis;
			$gor = $dispcon_data->order_id = $or;
			$gms = $dispcon_data->message = $msg;
			$merid = $dispcon_data->msid = $sids;
			$da = $dispcon_data->date = time();
			$nei = "Buyer";
			$cre = $dispcon_data->commented_by = $nei;

			$dispcon_data->itemdetail = json_encode($itemdet);
			$nefirst = "Initialized";
			$newstatus = $dispcon_data->newdispstatus = $nefirst;
				// print_r($diin);die;
			$this->Dispcons->save($dispcon_data);

			$userModel = $this->Users->find()->where(['id' => $userid])->first();//Byid($loguser[0]['User']['id']);
			$logusername = $userModel['username'];
			$logfirstname = $userModel['first_name'];
			$logusernameurl = $userModel['username_url'];
			$usrImg = $userModel['profile_image'];
			if (empty($usrImg))
				$usrImg = "usrimg.jpg";
			$image['user']['image'] = $usrImg;
			$image['user']['link'] = SITE_URL . "people/" . $logusernameurl;
			$loguserimage = json_encode($image);
			$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logfirstname . "</a>";
			$disputelink = "<a href='" . SITE_URL . "disputeBuyer/" . $or . "'>view</a>";
			$notifymsg = $loguserlink . " -___-created a dispute on your order " . $or . " : -___- " . $disputelink;
			$logdetails = $this->addlog('dispute', $userid, $merchantid, $dis, $notifymsg, $gms, $loguserimage);

				//push notification message

			/* hide by me	 $this->loadModel('Userdevices');
				 $logusername = $userName;
				 //$sourceId=$userid;
				 $userddett = $this->Userdevice->find('all',array('conditions'=>array('user_id'=>$merchantid)));
				 //echo "<pre>";print_r($userddett);die;
				 foreach($userddett as $userdet){
				 	$deviceTToken = $userdet['Userdevice']['deviceToken'];
				 	$badge = $userdet['Userdevice']['badge'];
				 	$badge +=1;
				 	$this->Userdevice->updateAll(array('badge' =>"'$badge'"), array('deviceToken' => $deviceTToken));
				 	if(isset($deviceTToken)){
				 		$messages = $logusername." created a dispute on your order ".$gor;
				 		$this->pushnot($deviceTToken,$messages,$badge);
				 	}
				 }*/

			/*	$this->Email->to = $merEmail;
				$this->Email->subject = "Dispute";
				$this->Email->from = $userEmail;
				$this->Email->sendAs = "html";
				$this->Email->template = 'userlogin';
				$this->set('UserId', $userid);
				$this->set('OrderId', $or);
				$this->set('Problem', $plm);
				$this->set('Message',$ms);
				$this->set('setngs',$setngs);
				$emailid = base64_encode($merEmail);
				//$pass = base64_encode($password);
				//$this->set('access_url',SITE_URL."verification/".$emailid."~".$refer_key."~".$pass);

				//$this->Email->send();

				$this->Session->setFlash(__('Dispute Created'));
				//$this->redirect('/dispute/');
				$this->redirect(array('controller' => '/', 'action' => 'dispute', $logusername,'?buyer'));*/

			/* hide by me	if($setngs[0]['Sitesetting']['gmail_smtp'] == 'enable'){
					$this->Email->smtpOptions = array(
						'port' => $setngs[0]['Sitesetting']['smtp_port'],
						'timeout' => '30',
						'host' => 'ssl://smtp.gmail.com',
						'username' => $setngs[0]['Sitesetting']['noreply_email'],
						'password' => $setngs[0]['Sitesetting']['noreply_password']);

					$this->Email->delivery = 'smtp';
				}
				$this->Email->to = $merEmail;
				//$this->Email->subject = "Dispute Created";
				$this->Email->subject = $setngs[0]['Sitesetting']['site_name']." – Dispute initiated on your order #".$gor;
				$this->Email->from = SITE_NAME."<".$setngs[0]['Sitesetting']['noreply_email'].">";
				$this->Email->sendAs = "html";
				$this->Email->template = 'dispute';
				$this->set('UserId', $userid);
				$this->set('merName',$merName);
				$this->set('buyName',$buyName);
				$this->set('OrderId', $or);
				$this->set('Problem', $plm);
				$this->set('Message',$ms);
				$this->set('setngs',$setngs);
				$this->set('gli',$gli);
				$this->set('buyer_url',$buyer_url);
				$this->set('seller_url',$seller_url);
				$emailid = base64_encode($merEmail);
				//$pass = base64_encode($password);
				//$this->set('access_url',SITE_URL."verification/".$emailid."~".$refer_key."~".$pass);

				$this->Email->send();

				if($setngs[0]['Sitesetting']['gmail_smtp'] == 'enable'){
					$this->Email->smtpOptions = array(
						'port' => $setngs[0]['Sitesetting']['smtp_port'],
						'timeout' => '30',
						'host' => 'ssl://smtp.gmail.com',
						'username' => $setngs[0]['Sitesetting']['noreply_email'],
						'password' => $setngs[0]['Sitesetting']['noreply_password']);

					$this->Email->delivery = 'smtp';
				}
				$this->Email->to = $userEmail;
				$this->Email->subject = $setngs[0]['Sitesetting']['site_name']." – Dispute initiated for your order #".$gor;
				$this->Email->from = SITE_NAME."<".$setngs[0]['Sitesetting']['noreply_email'].">";;
				$this->Email->sendAs = "html";
				$this->Email->template = 'buyerdispute';
				$this->set('UserId', $userid);
				$this->set('OrderId', $or);
				$this->set('Problem', $plm);
				$this->set('Message',$ms);
				$this->set('setngs',$setngs);
				$this->set('merName',$merName);
				$this->set('buyName',$buyName);
				$this->set('buyer_url',$buyer_url);
				$this->set('seller_url',$seller_url);
				$this->set('gli',$gli);
				$emailid = base64_encode($userEmail);
				//$pass = base64_encode($password);
				//$this->set('access_url',SITE_URL."verification/".$emailid."~".$refer_key."~".$pass);
				$this->Email->send();*/

				echo '{"status": "true", "dispute_id": ' . $dis . ',"message":"Dispute Created"}';
				die;

			}

		}
		function cancelDisp()
		{

			$disid = $_POST['dispute_id'];
			$userid = $_POST['user_id'];
			$type = $_POST['type'];

			$this->loadModel('Disputes');
			$this->loadModel('Users');
			$this->loadModel('Orders');
			// if($userid!="")
			//	$condition = array('userid'=>$userid);
			if ($disid != "")
				$condition = array('disid' => $disid);
			elseif ($disid != "" && $userid != "")
				$condition = array('disid' => $disid, 'userid' => $userid);
			$disp_data = $this->Disputes->find()->where(['disid' => $disid])->first();
			$orderid = $disp_data['uorderid'];
			$order_data = $this->Orders->find()->where(['orderid' => $orderid])->first();
			$merchantid = $order_data['merchant_id'];

			if ($type == 'cancel') {
				$this->Disputes->updateAll(array('newstatusup' => "Cancel"), $condition);
				$notifymsg = $loguserlink . " -___-Buyer Cancelled the Dispute " . $orderid . " : -___- " . $disputelink;
			} else {
				$this->Disputes->updateAll(array('newstatusup' => "Resolved"), $condition);
				$notifymsg = $loguserlink . " -___-Buyer Resolved the Dispute " . $orderid . " : -___- " . $disputelink;
			}
		$userModel = $this->Users->find()->where(['id' => $userid])->first();//yid($loguser[0]['User']['id']);
			//push notification
		$logusername = $userModel['username'];
		$logfirstname = $userModel['first_name'];
		$logusernameurl = $userModel['username_url'];

		$userImg = $userModel['profile_image'];
		if (empty($userImg)) {
			$userImg = 'usrimg.jpg';
		}
		$image['user']['image'] = $userImg;

		$image['user']['link'] = SITE_URL . "people/" . $logusernameurl;
		$loguserimage = json_encode($image);
		$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logfirstname . "</a>";
		$disputelink = "<a href='" . SITE_URL . "disputeBuyer/" . $orderid . "'>view</a>";
		$gmsss = 'Cancel Disputes';

		$logdetails = $this->addlog('dispute', $userid, $merchantid, $disid, $notifymsg, $gmsss, $loguserimage);
		echo '{"status": "true", "message": "Dispute Cancelled"}';
		die;
	}
	function closedDisp()
	{

		$this->loadModel('Disputes');
		$this->loadModel('Dispcons');
		$this->loadModel('Items');
		$this->loadModel('Shops');
		$this->loadModel('Photos');
		$this->loadModel('Users');
		$this->loadModel('Sitesettings');
		$this->loadModel('Order_items');

		$timeline = strtotime('-10 day');
		$userId = $_POST['user_id'];

		$userDetail = $this->Users->find()->where(['id' => $userId])->first();

		$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();
		$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();

		if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "") {
			$cur_symbol = $forexrateModel['currency_symbol'];

			$cur = $forexrateModel['price'];
		} else {
			$cur_symbol = $currency_value['currency_symbol'];
			$cur = $currency_value['price'];
		}
		$setngs = $this->Sitesettings->find()->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}
		if (isset($_POST['limit'])) {
			$limit = $_POST['limit'];
		} else {
			$limit = 10;
		}
		$status = array('Reply' => 'Reply', 'Initialized' => 'Initialized', 'Reopen' => 'Reopen', 'Responded' => 'Responded', 'Accepeted' => 'Accepeted');

		if (isset($_POST['offset'])) {
			$activedisp_data = $this->Disputes->find('all', array(
				'conditions' => array(
					'userid' => $userId,
					'newstatusup NOT IN' => $status,

				),
				'limit' => $limit,
				'offset' => $_POST['offset'],
				'order' => 'disid DESC',
			));

		} else {
			$activedisp_data = $this->Disputes->find('all', array(
				'conditions' => array(
					'userid' => $userId,
					'newstatusup NOT IN' => $status,
				),
				'limit' => $limit,
				'order' => 'disid DESC',
			));
		}
		$resultarray = array();
		foreach ($activedisp_data as $key => $activedisp_datas) {

			$resultarray[$key]['dispute_id'] = $activedisp_datas['disid'];
			$resultarray[$key]['status'] = $activedisp_datas['newstatusup'];
			$resultarray[$key]['currency'] = $cur_symbol;
			if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "")
				$resultarray[$key]['price'] = $activedisp_datas['totprice'];
			else
				$resultarray[$key]['price'] = $activedisp_datas['totprice'];
			$resultarray[$key]['dispute_date'] = $activedisp_datas['chatdate'];
			$order_data = $this->Order_items->find()->where(['orderid' => $activedisp_datas['uorderid']])->first();
			$itemId = $order_data['itemid'];
			$item_data = $this->Items->find()->where(['id' => $itemId])->first();
			$resultarray[$key]['item_title'] = $item_data['item_title'];
			$resultarray[$key]['item_id'] = $item_data['id'];
			$photo = $this->Photos->find()->where(['item_id' => $item_data['id']])->first();
			if ($photo['image_name'] == "") {
				$itemImage = "usrimg.jpg";
			} else {
				$itemImage = $photo['image_name'];
			}

			$resultarray[$key]['image'] = $img_path . 'media/items/thumb350/' . $itemImage;
			$shop_data = $this->Shops->find()->where(['id' => $item_data['shop_id']])->first();
			$resultarray[$key]['shop_id'] = $shop_data['id'];
			$resultarray[$key]['shop_name'] = $shop_data['shop_name'];

			if ($shop_data['shop_image'] == "") {
				$shopImage = "usrimg.jpg";
			} else {
				$shopImage = $shop_data['shop_image'];

			}
			$resultarray[$key]['shop_image'] = $img_path . 'media/avatars/thumb350/' . $shopImage;
			$activedisp_cons = $this->Dispcons->find('all', array(
				'conditions' => array(
					'dispid' => $activedisp_datas['disid'],
				),
				'limit' => 1,
				'order' => 'dcid DESC',
			));
			foreach ($activedisp_cons as $cons) {
				if ($cons['commented_by'] == "Seller")
					$resultarray[$key]['last_replied'] = $activedisp_datas['selid'];
				else
					$resultarray[$key]['last_replied'] = $activedisp_datas['userid'];

			}

		}
		if (!empty($resultarray)) {

			echo '{"status": "true", "result": ' . json_encode($resultarray) . '}';
			die;

		} else {
			echo '{"status": "false", "message": "No data found"}';
			die;

		}

	}

	function sendDispMessage()
	{
		$this->loadModel('Disputes');
		$this->loadModel('Dispcons');
		$this->loadModel('Orders');
		$this->loadModel('Users');
		$this->loadModel('Sitesettings');
		$user_id = $_POST['user_id'];

		$dispute_id = $_POST['dispute_id'];
		$message = $_POST['message'];
		$attachment = $_POST['attachment'];

		$img = explode("/", $attachment);
		$attachmentImage = end($img);

		$setngs = $this->Sitesettings->find()->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}
		$status = array('Reply' => 'Reply', 'Initialized' => 'Initialized', 'Reopen' => 'Reopen', 'Responded' => 'Responded', 'Accepeted' => 'Accepeted');

		$dispdet = $this->Disputes->find()->where(['disid' => $dispute_id])->andWhere(['newstatusup IN' => $status])->first();
		$orderid = $dispdet['uorderid'];

		$orderdet = $this->Orders->find()->where(['orderid' => $orderid])->first();
		$sellerid = $orderdet['merchant_id'];
		if (count($dispdet) != 0) {
			$dispcon = $this->Dispcons->find()->where(['dispid' => $dispute_id])->first();
			if (count($dispcon) == 0)
				$rly = 'Initialized';
			else
				$rly = "Reply";
			if ($user_id == $dispdet['userid'])
				$commentedBy = "Buyer";
			else
				$commentedBy = "Seller";
			$orderId = $dispdet['uorderid'];
			$msid = $dispdet['selid'];
				//$user_id = $dispdet['userid'];
		} else {
			echo '{"status": "false", "message": "Something went to be wrong,Please try again later."}';
			die;
		}

		$dispcon_data = $this->Dispcons->newEntity();
		$cuids = $dispcon_data->user_id = $user_id;
						//$gli=$this->request->data['Dispcon']['dispid']=$dis;
		$gor = $dispcon_data->order_id = $orderid;
		$gmsss = $dispcon_data->message = $message;
		$merid = $dispcon_data->msid = $sellerid;
		$liid = $dispcon_data->dispid = $dispute_id;
		$da = $dispcon_data->date = time();

		$cre = $dispcon_data->commented_by = $commentedBy;
		if ($attachment != "") {
			$dispcon_data->imagedisputes = $attachmentImage;
		}

		$rly = 'Reply';
		$dispcon_data->newdispstatus = $rly;
		$this->Dispcons->save($dispcon_data);

		$resp = 'Responded';
		$chtim = time();
		$this->Disputes->updateAll(array('newstatusup' => $resp, 'chatdate' => $chtim), array('disid' => $dispute_id));
		$userModel = $this->Users->find()->where(['id' => $user_id])->first();
		$logusername = $userModel['username'];
		$logfirstname = $userModel['first_name'];
		$logusernameurl = $userModel['username_url'];
		$userImg = $userModel['profile_image'];
		if (empty($userImg)) {
			$userImg = 'usrimg.jpg';
		}
		$image['user']['image'] = $userImg;
		$image['user']['link'] = SITE_URL . "people/" . $logusernameurl;
		$loguserimage = json_encode($image);
		$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logfirstname . "</a>";
		$disputelink = "<a href='" . SITE_URL . "disputeBuyer/" . $gor . "'>view</a>";
		$notifymsg = $loguserlink . " -___-Buyer Replied For the Dispute " . $gor . " : -___- " . $disputelink;
		$logdetails = $this->addlog('dispute', $user_id, $sellerid, $dispute_id, $notifymsg, $gmsss, $loguserimage);
		$resultArray = array();
		if ($message == "") {
			$resultArray['type'] = "attachment";
			$resultArray['message'] = "";
			$resultArray['attachment'] = $attachment;
		} else {
			$resultArray['type'] = "message";
			$resultArray['message'] = $message;
			$resultArray['attachment'] = "";
		}
		$resultArray['user_name'] = $userModel['username'];
		$resultArray['full_name'] = $userModel['first_name'] . ' ' . $userModel['last_name'];
		$resultArray['user_id'] = $userModel['id'];
		$resultArray['user_image'] = $img_path . 'media/avatars/' . $userImg;
		$resultArray['chat_date'] = $chtim;
		echo '{"status": "true", "result": ' . json_encode($resultArray) . '}';
		die;

	}
	function setSettings()
	{

		$userId = $_POST['user_id'];
		$userImage = $_POST['user_image'];
		$fullName = $_POST['full_name'];
		$currencyId = $_POST['currency_id'];
		$someoneFollowEmail = $_POST['someone_follow_email'];
		$someoneFollowPush = $_POST['someone_follow_notify'];
		$someoneMentionNotify = $_POST['someone_mention_notify'];
		$storeProductAdded = $_POST['store_product_added'];
		$receiveNewsAdmin = $_POST['receive_news_admin'];
		$language = $_POST['language'];
		$this->loadModel('Users');
		$user_data = $this->Users->find()->where(['id' => $userId])->first();
		if (count($user_data) != 0) {
			if ($userImage != "") {
				$user_data->profile_image = $userImage;
			}
			if ($currencyId != "") {
				$user_data->currencyid = $currencyId;
			}
			if ($fullName != "") {
				$user_data->first_name = $fullName;
			}

			if ($language != "") {
				$user_data->languagecode = $language;
			}
			$decoded_value = json_decode($user_data['push_notifications']);
			if ($someoneFollowEmail == 'true') {
				$user_data->someone_follow = 1;
			} elseif ($someoneFollowEmail == 'false') {
				$user_data->someone_follow = 0;
			}
			if ($storeProductAdded == 'true') {
				$decoded_value->frends_flw_push = 1;
			} elseif ($storeProductAdded == 'false') {
				$decoded_value->frends_flw_push = 0;
			}
			if ($receiveNewsAdmin == 'true') {
				$user_data->subs = 1;
			} elseif ($receiveNewsAdmin == 'false') {
				$user_data->subs = 0;
			}
			if ($someoneFollowPush == 'true') {
				$decoded_value->somone_flw_push = 1;
			} elseif ($someoneFollowPush == 'false') {
				$decoded_value->somone_flw_push = 0;
			}
			if ($someoneMentionNotify == 'true') {
				$decoded_value->somone_mentions_push = 1;
			} elseif ($someoneMentionNotify == 'false') {
				$decoded_value->somone_mentions_push = 0;
			}
			$decoded_value->somone_likes_ur_item_push = $decoded_value->somone_likes_ur_item_push;
			$decoded_value->somone_cmnts_push = $decoded_value->somone_cmnts_push;
			$decoded_value->frends_cmnts_push = $decoded_value->frends_cmnts_push;
			$user_data->push_notifications = json_encode($decoded_value);

			$lang_details = TableRegistry::get('Languages')->find()->where(['languagecode' => $_POST['language']])->first();
			unset($_SESSION['languagecode']);
			unset($_SESSION['languagename']);
			$_SESSION['languagecode'] = $lang_details['languagecode'];
			$_SESSION['languagename'] = $lang_details['languagename'];

			if ($this->Users->save($user_data)) {
				echo '{"status": "true", "message": "Settings saved successfully"}';
				die;
			} else {
				echo '{"status": "false", "message": "Something went to be wrong,Please try again later."}';
				die;
			}
		} else {
			echo '{"status": "false", "message": "Something went to be wrong,Please try again later."}';
			die;

		}

	}
	function getSettings()
	{

		$userId = $_POST['user_id'];
		$this->loadModel('Users');
		$this->loadModel('Forexrates');
		$this->loadModel('Languages');
		$user_data = $this->Users->find()->where(['id' => $userId])->first();
		if (count($user_data) == 0) {
			echo '{"status": "false", "message": "No data found"}';
			die;
		}
		$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();
		$userCurrency = $this->Forexrates->find()->where(['id' => $user_data['currencyid']])->first();
		if ($user_data['currencyid'] == "") {
			$curid = $forexrateModel['id'];
			$cur_symbol = $forexrateModel['currency_code'];
			$currency_symbol = $forexrateModel['currency_symbol'];
		} else {
			$curid = $userCurrency['id'];
			$cur_symbol = $userCurrency['currency_code'];
			$currency_symbol = $userCurrency['currency_symbol'];
		}
		$resultArray = array();
		$resultArray['currency_id'] = $curid;
		$resultArray['currency_symbol'] = $currency_symbol;
		$resultArray['currency_code'] = $cur_symbol;
		if ($user_data['languagecode'] == "") {
			$resultArray['language'] = "en";
		} else {
			$resultArray['language'] = $user_data['languagecode'];
		}
		if ($user_data['password'] == "") {
			$resultArray['has_password'] = "no";
		} else {
			$resultArray['has_password'] = "yes";
		}
		$decoded_value = json_decode($user_data['push_notifications']);
		if ($user_data['someone_follow'] == 1)
			$resultArray['someone_follow_email'] = 'true';
		else
			$resultArray['someone_follow_email'] = 'false';
		if ($decoded_value->somone_flw_push == 1)
			$resultArray['someone_follow_notify'] = 'true';
		else
			$resultArray['someone_follow_notify'] = 'false';
		if ($decoded_value->somone_mentions_push == 1)
			$resultArray['someone_mention_notify'] = 'true';
		else
			$resultArray['someone_mention_notify'] = 'false';
		if ($decoded_value->frends_flw_push == 1)
			$resultArray['store_product_added'] = 'true';
		else
			$resultArray['store_product_added'] = 'false';
		if ($user_data['subs'] == 1)
			$resultArray['receive_news_admin'] = 'true';
		else
			$resultArray['receive_news_admin'] = 'false';
		$resultArray['currency'] = array();

		$language_data = $this->Languages->find()->all();
		foreach ($language_data as $key => $language_datas) {
			$currency_codes[] = $language_datas['countrycode'];

		}
		$currency_data = $this->Forexrates->find()->where(['currency_code IN' => $currency_codes])->all();
		foreach ($currency_data as $key => $currency_datas) {
			$resultArray['currency'][$key]['currency_id'] = $currency_datas['id'];
			$resultArray['currency'][$key]['currency_code'] = $currency_datas['currency_code'];
		}
		foreach ($language_data as $key => $language_datas) {
			$resultArray['languages'][$key]['code'] = $language_datas['languagecode'];
			$resultArray['languages'][$key]['name'] = $language_datas['languagename'];
		}
		if (!empty($resultArray)) {
			echo '{"status": "true", "result": ' . json_encode($resultArray) . '}';
			die;

		} else {
			echo '{"status": "false", "message": "No data found"}';
			die;
		}
	}

	function loginWithSocial()
	{

		$this->loadModel('Users');
		$this->loadModel('Sitesettings');
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->first();
		$type = $_POST['type'];
		$socialId = $_POST['id'];
		$socialFirstName = urldecode($_POST['full_name']);

		$socialEmail = $_POST['email'];
		if ($socialLastName != '') {
			$socialFirstName .= " " . $socialLastName;
		}
		$status = false;
		$userName = "";
		$photo = "";

		if (SITE_URL == $_SESSION['media_url']) {
			$img_path = $_SESSION['media_url'];
		} else {
			$img_path = $_SESSION['media_url'];
		}
		if (!empty($_POST['imageurl'])) {

			$imageurl = $_POST['imageurl'];
			$imageName = time() . '_' . rand(0, 9) . ".jpg";
			$temp = $this->upload($imageurl, $imageName);

		} else {
			$imageName = 'usrimg.jpg';
			$temp = 'usrimg.jpg';
		}

		if($type == 'apple')
		{
			$userModel = $this->Users->find()->where(['apple_id' => $socialId])->first();
		}else{
			$userModel = $this->Users->find()->where(['email' => $socialEmail])->first();
		}

		//echo '<pre>'; print_r($userModel); die;
		//all',array('conditions'=>array('email'=>$socialEmail)));

		//echo '<pre>'; print_r($userModel); die;
		if ($userModel['user_status'] == 'disable') {
			echo '{"status":"error","message":"The user has been blocked by admin"}';
			die;
		}
		if ($userModel['user_level'] == 'god') {
			echo '{"status":"false","message":"You cannot login as Admin"}';
			die;
		}
		if ($userModel['user_level'] == 'moderator') {
			echo '{"status":"false","message":"You cannot login as Moderator"}';
			die;
		}

		if ($userModel['user_level'] == 'shop') {
			echo '{"status":"false","message":"You Cannot login as Merchant"}';
			die;
		}

		if (count($userModel) > 0) {
			$user_api_details = json_decode($userModel['user_api_details'], true);
			$userid = $userModel['id'];
			$userName = $userModel['first_name'];
			if ($userModel['profile_image'] != "") {
				$photo = $img_path . "media/avatars/original/" . $userModel['profile_image'];
			} else {
				$userModel->profile_image = $imageName;
				$photo = $img_path . "media/avatars/original/" . $imageName;
			}
			if ($type == "facebook") {

				if (!empty($user_api_details)) {
					$user_api_details['socialLoginDetails']['facebookName'] = $socialFirstName;
				} else {
					$user_api_details['socialLoginDetails']['facebookName'] = $socialFirstName;
				}
				$userModel->facebook_id = $socialId;
			} elseif ($type == "google") {
				if (!empty($user_api_details)) {
					$user_api_details['socialLoginDetails']['googleEmail'] = $socialFirstName;
				} else {
					$user_api_details['socialLoginDetails']['googleEmail'] = $socialFirstName;

				}
				$userModel->google_id = $socialId;
			}elseif ($type == "apple") {
				if (!empty($user_api_details)) {
					$user_api_details['socialLoginDetails']['appleEmail'] = $socialFirstName;
				} else {
					$user_api_details['socialLoginDetails']['appleEmail'] = $socialFirstName;

				}
				$userModel->apple_id = $socialId;
			}

			if($type != 'apple')
			{
				$user_api_details = json_encode($user_api_details);
				$userModel->user_api_details = $user_api_details;
			}
			
			//echo $user_api_details; die;
			$last_login = date('Y-m-d H:i:s');
			$userModel->last_login = $last_login;
			$userModel->id = $userModel['id'];
			$this->Users->save($userModel);
			$status = true;
		} else {
			$user_data = $this->Users->newEntity();
			if ($type == "facebook") {
				$user_api_details['socialLoginDetails']['facebookName'] = $socialFirstName;
				$user_api_details = json_encode($user_api_details);
				$user_data->facebook_id = $socialId;
				$user_data->login_type = 'facebook';
			} elseif ($type == "google") {
				$user_api_details['socialLoginDetails']['googleEmail'] = $socialFirstName;
				$user_api_details = json_encode($user_api_details);
				$user_data->google_id = $socialId;
				$user_data->login_type = 'google';
			} elseif ($type == "apple") {
				$user_api_details['socialLoginDetails']['appleEmail'] = $socialFirstName;
				$user_api_details = json_encode($user_api_details);
				$user_data->apple_id = $socialId;
				$user_data->login_type = 'apple';
			}

			
			//echo '<pre>'; print_r($user_data); die;
			$userName = $socialFirstName;
			$user_data->user_api_details = $user_api_details;
			$user_data->username = $socialFirstName;
			$user_data->username_url = $this->Urlfriendly->utils_makeUrlFriendly($socialFirstName);
			$user_data->first_name = $socialFirstName;
			$user_data->email = $socialEmail;
			$user_data->user_level = 'normal';
			$user_data->user_status = 'enable';
			$user_data->push_notifications = '{"somone_flw_push":"1",
			"somone_cmnts_push":"1","somone_mentions_push":"1","somone_likes_ur_item_push":"1",
			"frends_flw_push":1,"frends_cmnts_push":1}';
			$user_data->activation = '1';
			$user_data->credit_points = $setngs['signup_credit'];
			$user_data->created_at = date('Y-m-d H:i:s');
			$user_data->profile_image = $temp;
			$uniquecode = $this->Urlfriendly->get_uniquecode(8);
			$refer_key = $user_data->refer_key = $uniquecode;

			$result = $this->Users->save($user_data);
			$userid = $result->id;

			$this->loadModel('Shops');
			$shop_data = $this->Shops->newEntity();
			$shop_data->user_id = $userid;
			$shop_data->seller_status = '2';
			$this->Shops->save($shop_data);
			$user_data1 = $this->Users->find()->where(['id' => $userid])->first();
			$userName = str_replace(" ", "", $userName);
			$userName .= $userid;
			$user_data1->username = $userName;
			$user_data1->username_url = $this->Urlfriendly->utils_makeUrlFriendly($userName);
			$user_data1->id = $userid;
			$this->Users->save($user_data1);
			$photo = $img_path . "media/avatars/original/" . $imageName;
			$status = true;
		}
		if ($status == 'true') {
			$userdata = $this->Users->find()->where(['id' => $userid])->first();//ById($userid);
			if (strtotime($userdata['last_login']) == "")
				$first_time_logged = "yes";
			else
				$first_time_logged = "no";
			if ($photo == "") {
				$photo = $img_path . "media/avatars/original/usrimg.jpg";
			}
			echo '{"status":"true","user_id":"' . $userid . '","user_name":"' . $userdata['username'] . '","user_image":"' . $photo . '"
			,"full_name":"' . $userdata['first_name'] . '","first_time_logged":"' . $first_time_logged . '"}';
			die;
		} else {
			echo '{"status":"false","message":"Unable to save the data now"}';
			die;
		}
	}

	function editComments()
	{

		$this->loadModel('Comments');
		$this->loadModel('Items');
		$this->loadModel('Users');
		$this->loadModel('Hashtags');
		$this->loadModel('Sitesettings');
		$this->loadModel('Photos');
		$this->loadModel('Itemfavs');
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
		if (SITE_URL == $_SESSION['media_url']) {
			$img_path = $_SESSION['media_url'];
		} else {
			$img_path = $_SESSION['media_url'];
		}
		if (isset($_POST)) {
			$userId = $_POST['user_id'];

			$itemId = $_POST['item_id'];
			$commentId = $_POST['comment_id'];
			$pushcomment = $_POST['comment'] . " ";
			$comment = $_POST['comment'] . " ";
			$usedHashtag = '';
			$oldHashtags = array();
			$loguser = $this->Users->find()->where(['id' => $userId])->first();
			preg_match_all('/#([\S]*?)(?=\s)/', $comment, $hashmatch);
				//echo "<pre>"; print_r($hashmatch); die;
			if (!empty($hashmatch)) {
				foreach ($hashmatch[1] as $hashtag) {
					$cleanedHashtag = preg_replace('/[^A-Za-z0-9\-]/', '', $hashtag);
					if (!empty($cleanedHashtag) && $cleanedHashtag != '') {
						$hashtag = $cleanedHashtag;
						if ($usedHashtag == '') {
							$usedHashtag = $hashtag;
						}
						$usedHashtag .= ',' . $hashtag;
						$comment = str_replace('#' . $hashtag . " ", '<span class="hashatcolor">#</span><a href="' . SITE_URL . 'hashtag/' . $hashtag . '">' . $hashtag . '</a> ', $comment);
					}
				}

				$hashTags = explode(',', $usedHashtag);

				$hashtagsModel = $this->Hashtags->find()->where(['hashtag IN' => $hashTags])->all();

				if (!empty($hashtagsModel)) {
					foreach ($hashtagsModel as $hashtags) {
						$id = $hashtags['id'];
						$count = $hashtags['usedcount'] + 1;
						$this->Hashtags->updateAll(array('usedcount' => $count), array('id' => $id));
						$oldHashtags[] = $hashtags['hashtag'];
					}
				}
				foreach ($hashTags as $hashtag) {
					if (!in_array($hashtag, $oldHashtags)) {
						$hashtag_data = $this->Hashtags->newEntity();
						$hashtag_data->hashtag = $hashtag;
						$hashtag_data->usedcount = 1;
						$this->Hashtags->save($hashtag_data);
					}
				}
			}
			preg_match_all('/@([\S]*?)(?=\s)/', $comment, $atmatch);

			$mentionedUsers = "";
			if (!empty($atmatch)) {
				foreach ($atmatch[1] as $atuser) {
					$cleanedAtUser = preg_replace('/[^A-Za-z0-9\-]/', '', $atuser);
					if (!empty($cleanedAtUser) && $cleanedAtUser != '') {
						$atuser = $cleanedAtUser;
						$comment = str_replace('@' . $atuser . " ", '<span class="hashatcolor">@</span><a href="' . SITE_URL . 'people/' . $atuser . '">' . $atuser . '</a> ', $comment);
						$mentionedUsers = $mentionedUsers != "" ? "," . $atuser : $atuser;
					}
				}
			}
			$comment_data = $this->Comments->find()->where(['id' => $commentId])->first();
			if (count($comment_data) == 0) {
				echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
				die;

			}
			$comment_data->user_id = $userId;
			$comment_data->item_id = $itemId;
			$comment_data->comments = $comment;
			$result = $this->Comments->save($comment_data);
			$resultArray = array();
			$resultArray['comment_id'] = $result->id;
			$id = $result->id;
			$userModel = $this->Users->find()->where(['id' => $userId])->first();//first',array('conditions'=>array('User.id'=>$userId)));

			$path = $img_path . "media/avatars/thumb70/";

			if (!empty($userModel["profile_image"])) {
				$path .= $userModel['profile_image'];
			} else {
				$path .= 'usrimg.jpg';
			}
			$commentEncoded = urldecode($pushcomment);

			$userdatasall = $this->Items->find()->where(['id' => $itemId])->first();//ById($itemId);
			$photo = $this->Photos->find()->where(['item_id' => $itemId])->first();

			if ($mentionedUsers != "") {
				$mentionedUsers = explode(",", $mentionedUsers);
				foreach ($mentionedUsers as $musers) {

					$userModel = $this->Users->find()->where(['username' => $musers])->first();
					$notificationSettings = json_decode($userModel['push_notifications'], true);
					$notifyto = $loguser['id'];
					if ($notificationSettings['somone_mentions_push'] == 1 && $userId != $notifyto) {
						$logusername = $loguser['username'];
						$logusernameurl = $loguser['username_url'];
						$itemname = $userdatasall['item_title'];
						$itemurl = $userdatasall['item_title_url'];
						$liked = $setngs[0]['liked_btn_cmnt'];
						if (!empty($loguser['profile_image'])) {
							$image['user']['image'] = $loguser['profile_image'];
						} else {
							$image['user']['image'] = 'usrimg.jpg';
						}
						$image['user']['link'] = SITE_URL . "people/" . $logusernameurl;
						$image['item']['image'] = $photo['image_name'];
						$image['item']['link'] = SITE_URL . "listing/" . $itemId . "/" . $itemurl;
						$loguserimage = json_encode($image);
						$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logusername . "</a>";
						$productlink = "<a href='" . SITE_URL . "listing/" . $itemId . "/" . $itemurl . "'>" . $itemname . "</a>";
						$notifymsg = $loguserlink . " -___-mentioned you in a comment on: -___- " . $productlink;

						$logdetails = $this->addlog('mentioned', $userId, $notifyto, $id, $notifymsg, $comment, $loguserimage, $itemId);
						/*	App::import('Controller', 'Users');
							$Users = new UsersController;
							$this->loadModel('Userdevice');
							//$usernamedetails = $this->User->findById($userId);
							//$loginusername = $usernamedetails['User']['username']; //echo $loginusername;;
							$userddett = $this->Userdevice->findAllByuser_id($notifyto);

							foreach($userddett as $userddet){
							  $deviceTToken = $userddet['Userdevice']['deviceToken'];
							  $badge = $userdet['Userdevice']['badge'];
							  $badge +=1;
							  $this->Userdevice->updateAll(array('badge' =>"'$badge'"), array('deviceToken' => $deviceTToken));
								if(isset($deviceTToken)){
									$messages = $logusername." mentioned you in a comment on product: ".$itemname;
									$Users->pushnot($deviceTToken,$messages,$badge);
								}
							}*/
						}
					}
				}
				$favUsers = $this->Itemfavs->find()->where(['user_id' => $userId])->all();
				if (!empty($favUsers)) {
					foreach ($favUsers as $fuser) {
						$userModels = $this->Users->find()->where(['id' => $fuser['user_id']])->first();
						$notifyto = $userdatasall['id'];
						$notificationSettings = json_decode($userModels['push_notifications'], true);
						if ($notificationSettings['somone_cmnts_push'] == 1 && $userId != $notifyto) {
							$favnotifyto[] = $userModels['id'];
						}
					}
				//	$loguser = $this->Users->find()->where(['id'=>$userId])->first();//all',array('conditions'=>array('User.id'=>$userId)));
					$logusername = $loguser['username'];
					$logusernameurl = $loguser['username_url'];
					if (!empty($favnotifyto)) {
						$itemname = $userdatasall['item_title'];
						$itemurl = $userdatasall['item_title_url'];
						$liked = $setngs[0]['liked_btn_cmnt'];
						$image['user']['image'] = $loguser['profile_image'];
						$image['user']['link'] = SITE_URL . "people/" . $logusernameurl;
						$image['item']['image'] = $photo['image_name'];
						$image['item']['link'] = SITE_URL . "listing/" . $itemId . "/" . $itemurl;
						$loguserimage = json_encode($image);
						$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logusername . "</a>";
						$productlink = "<a href='" . SITE_URL . "listing/" . $itemId . "/" . $itemurl . "'>" . $itemname . "</a>";
						$notifymsg = $loguserlink . " -___-commented on-___- " . $productlink;
						$logdetails = $this->addlog('comment', $userId, $favnotifyto, $id, $notifymsg, $comment, $loguserimage);
					}
				}

				echo '{"status":"true","message":"Edited successfully"}';
				die;
				//echo '{"status":"true","comment_id":"'.$id.'","comment":"'.$commentEncoded.'","user_id":"'.$userId.'","user_image":"'.$path.'","user_name":"'.$username.'","full_name":"'.$fullname.'"}';die;

			}
		}
		function deleteComments()
		{

			$this->loadModel('Comments');

			$this->loadModel('Hashtags');
			$this->loadModel('Logs');

			if (isset($_POST)) {
				$userId = $_POST['user_id'];

				$itemId = $_POST['item_id'];
				$commentId = $_POST['comment_id'];
				$comment_data = $this->Comments->find()->where(['id' => $commentId])->first();
				if (count($comment_data) == 0) {
					echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
					die;

				}
				$comment = $comment_data['comments'];

				$usedHashtag = '';
				$oldHashtags = array();
				preg_match_all('/#([\S]*?)(?=\s)/', $comment, $hashmatch);

				if (!empty($hashmatch)) {
					foreach ($hashmatch[1] as $hashtag) {
						$cleanedHashtag = preg_replace('/[^A-Za-z0-9\-]/', '', $hashtag);
						if (!empty($cleanedHashtag) && $cleanedHashtag != '') {
							$hashtag = $cleanedHashtag;
							if ($usedHashtag == '') {
								$usedHashtag = $hashtag;
							}
							$usedHashtag .= ',' . $hashtag;

						}
					}
					$hashTags = explode(',', $usedHashtag);

				$hashtagsModel = $this->Hashtags->find()->where(['hashtag IN' => $hashTags])->all();//all',array(
				if (!empty($hashtagsModel)) {
					foreach ($hashtagsModel as $hashtags) {
						$id = $hashtags['id'];
						$count = $hashtags['usedcount'] - 1;
						$this->Hashtags->updateAll(array('usedcount' => $count), array('id' => $id));
						$oldHashtags[] = $hashtags['hashtag'];
					}
				}
			}

			$this->Comments->deleteAll(array('id' => $commentId));

			$this->Logs->deleteAll(array('sourceid' => $commentId));

			echo '{"status":"true","message":"Deleted successfully"}';
			die;

		}
	}
	function editpostComment()
	{
		$this->loadModel('Feedcomments');
		$this->loadModel('Items');
		$this->loadModel('Users');
		$this->loadModel('Logs');
		$this->loadModel('Hashtags');
		$this->loadModel('Sitesettings');
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
		if (SITE_URL == $_SESSION['media_url']) {
			$img_path = $_SESSION['media_url'];
		} else {
			$img_path = $_SESSION['media_url'];
		}
		if (isset($_POST)) {
			$userId = $_POST['user_id'];

			$feedId = $_POST['feed_id'];
			$commentId = $_POST['comment_id'];
			$pushcomment = $_POST['comment'] . " ";
			$comment = $_POST['comment'] . " ";
			$usedHashtag = '';
			$oldHashtags = array();
			preg_match_all('/#([\S]*?)(?=\s)/', $comment, $hashmatch);
					//echo "<pre>"; print_r($hashmatch);
			if (!empty($hashmatch)) {
				foreach ($hashmatch[1] as $hashtag) {
					$cleanedHashtag = preg_replace('/[^A-Za-z0-9\-]/', '', $hashtag);
					if (!empty($cleanedHashtag) && $cleanedHashtag != '') {
						$hashtag = $cleanedHashtag;
						if ($usedHashtag == '') {
							$usedHashtag = $hashtag;
						}
						$usedHashtag .= ',' . $hashtag;
						$comment = str_replace('#' . $hashtag . " ", '<span class="hashatcolor">#</span><a href="' . SITE_URL . 'hashtag/' . $hashtag . '">' . $hashtag . '</a> ', $comment);
					}
				}
				$hashTags = explode(',', $usedHashtag);
				$hashtagsModel = $this->Hashtags->find()->where(['hashtag IN' => $hashTags])->all();
				if (!empty($hashtagsModel)) {
					foreach ($hashtagsModel as $hashtags) {
						$id = $hashtags['id'];
						$count = $hashtags['usedcount'] + 1;
						$this->Hashtags->updateAll(array('usedcount' => $count), array('id' => $id));
						$oldHashtags[] = $hashtags['hashtag'];
					}
				}
				foreach ($hashTags as $hashtag) {
					if (!in_array($hashtag, $oldHashtags)) {
						$hashtag_data = $this->Hashtags->newEntity();
						$hashtag_data->hashtag = $hashtag;
						$hashtag_data->usedcount = 1;
						$this->Hashtags->save($hashtag_data);
					}
				}
			}
			preg_match_all('/@([\S]*?)(?=\s)/', $comment, $atmatch);
					//echo "<pre>"; print_r($match);
			$mentionedUsers = "";
			if (!empty($atmatch)) {
				foreach ($atmatch[1] as $atuser) {
					$cleanedAtUser = preg_replace('/[^A-Za-z0-9\-]/', '', $atuser);
					if (!empty($cleanedAtUser) && $cleanedAtUser != '') {
						$atuser = $cleanedAtUser;
						$comment = str_replace('@' . $atuser . " ", '<span class="hashatcolor">@</span><a href="' . SITE_URL . 'people/' . $atuser . '">' . $atuser . '</a> ', $comment);
						$mentionedUsers = $mentionedUsers != "" ? "," . $atuser : $atuser;
					}
				}
			}
					//$userdatasall = $this->Item->findById($itemId);
			$userdatasall = $this->Users->find()->where(['id' => $userId])->first();//ById($userId);
			$loguser[0] = $userdatasall;
			$commentEncoded = urldecode($pushcomment);
			$userModel = $this->Users->find()->where(['id' => $userId])->first();//first',array('conditions'=>array('User.id'=>$userId)));
			$path = $img_path . "media/avatars/thumb70/";
			$username = $userModel['username'];
			$fullname = $userModel['first_name'] . ' ' . $userModel['last_name'];
			if (!empty($userModel["profile_image"])) {
				$path .= $userModel['profile_image'];
			} else {
				$path .= 'usrimg.jpg';
			}
			$username = $userModel['username'];
			$feedcomment_data = $this->Feedcomments->find()->where(['id' => $commentId])->first();//newEntity();
			if (count($feedcomment_data) == 0) {
				echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
				die;
			}
			$feedcomment_data->userid = $userId;
			$feedcomment_data->statusid = $feedId;
			$feedcomment_data->comments = $comment;
			$feedcomment_dataresult = $this->Feedcomments->save($feedcomment_data);
			$id = $feedcomment_dataresult->id;
			if ($mentionedUsers != "") {
				$mentionedUsers = explode(",", $mentionedUsers);
				foreach ($mentionedUsers as $musers) {
					$userModel = $this->Users->find()->where(['username' => $musers])->first();
					$notificationSettings = json_decode($userModel['push_notifications'], true);
					$notifyto = $userModel['id'];
					if ($notificationSettings['somone_mentions_push'] == 1 && $userId != $notifyto) {
						$logusername = $loguser[0]['username'];
						$logfirstname = $loguser[0]['first_name'];
						$logusernameurl = $loguser[0]['username_url'];
								//$itemname = $userdatasall['Item']['item_title'];
								//$itemurl = $userdatasall['Item']['item_title_url'];
						$liked = $setngs[0]['liked_btn_cmnt'];
						$userImg = $loguser[0]['profile_image'];
						if (empty($userImg)) {
							$userImg = 'usrimg.jpg';
						}
						$image['user']['image'] = $userImg;
						$image['user']['link'] = SITE_URL . "people/" . $logusernameurl;
						$loguserimage = json_encode($image);
						$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logusername . "</a>";
						$loglink = "<a href='" . SITE_URL . "livefeed/" . $feedId . "'>" . $feedId . "</a>";
						$notifymsg = $loguserlink . " -___-mentioned you in a comment on : " . $loglink;
								//$logdetails = $this->addloglive('mentioned',$userId,$notifyto,$id,$notifymsg,$commentss,$loguserimage);
						$itemid = '-1';
						$logdetails = $this->addlog('mentioned', $userId, $notifyto, $id, $notifymsg, $comment, $loguserimage, $itemid);
						$userdevicestable = TableRegistry::get('Userdevices');
						$userddett = $userdevicestable->find('all')->where(['user_id' => $notifyto])->all();

						foreach ($userddett as $userdet) {
							$deviceTToken = $userdet['deviceToken'];
							$badge = $userdet['badge'];
							$badge += 1;
							$querys = $userdevicestable->query();
							$querys->update()
							->set(['badge' => $badge])
							->where(['deviceToken' => $deviceTToken])
							->execute();
							if (isset($deviceTToken)) {
								$pushMessage['type'] = 'mention_status';
								$pushMessage['user_id'] = $loguser[0]['id'];
								$pushMessage['feed_id'] = $comment_id;
								$pushMessage['user_name'] = $loguser[0]['username'];
								$pushMessage['user_image'] = $userImg;
								$user_detail = TableRegistry::get('Users')->find()->where(['id' => $notifyto])->first();
								I18n::locale($user_detail['languagecode']);
								$pushMessage['message'] = __d('user', "mentioned you in the status");
								$messages = json_encode($pushMessage);
								$this->pushnot($deviceTToken, $messages, $badge);
							}
						}

					}
				}
			}

			echo '{"status":"true","message":"Edited successfully"}';
			die;
		} else {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		}
	}
	function deletepostcomment()
	{

		$this->loadModel('Feedcomments');

		$this->loadModel('Hashtags');
		$this->loadModel('Logs');

		if (isset($_POST)) {
			$userId = $_POST['user_id'];

			$feedId = $_POST['feed_id'];
			$commentId = $_POST['comment_id'];
			$comment_data = $this->Feedcomments->find()->where(['id' => $commentId])->first();
			if (count($comment_data) == 0) {
				echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
				die;

			}
			$comment = $comment_data['comments'];

			$usedHashtag = '';
			$oldHashtags = array();
			preg_match_all('/#([\S]*?)(?=\s)/', $comment, $hashmatch);

			if (!empty($hashmatch)) {
				foreach ($hashmatch[1] as $hashtag) {
					$cleanedHashtag = preg_replace('/[^A-Za-z0-9\-]/', '', $hashtag);
					if (!empty($cleanedHashtag) && $cleanedHashtag != '') {
						$hashtag = $cleanedHashtag;
						if ($usedHashtag == '') {
							$usedHashtag = $hashtag;
						}
						$usedHashtag .= ',' . $hashtag;

					}
				}
				$hashTags = explode(',', $usedHashtag);

				$hashtagsModel = $this->Hashtags->find()->where(['hashtag IN' => $hashTags])->all();//all',array(
				if (!empty($hashtagsModel)) {
					foreach ($hashtagsModel as $hashtags) {
						$id = $hashtags['id'];
						$count = $hashtags['usedcount'] - 1;
						$this->Hashtags->updateAll(array('usedcount' => $count), array('id' => $id));
						$oldHashtags[] = $hashtags['hashtag'];
					}
				}
			}

			$this->Feedcomments->deleteAll(array('id' => $commentId));
			$log_datas = $this->Logs->find()->where(['id' => $feedId])->first();//ById($feedId);
			$counts = $log_datas['commentcount'];
			$counts = $counts - 1;
			$this->Logs->updateAll(array('commentcount' => $counts), array('id' => $feedId));

			echo '{"status":"true","message":"Deleted successfully"}';
			die;

		}
	}

	function paymentProcess()
	{
		$userId = $_POST['user_id'];
		$itemIds = $_POST['item_id'];
		$shippingId = $_POST['shipping_id'];
		$couponCode = $_POST['coupon_code'];
		$giftNo = $_POST['gift_no'];
		$creditAmount = $_POST['credit_amount'];
		$size = $_POST['size'];
		$today = strtotime(date("Y-m-d"));
		$shipcost = 0;
		$this->loadModel('Sellercoupons');
		$this->loadModel('Userinvitecredits');
		$this->loadModel('Items');
		$this->loadModel('Tempaddresses');
		$this->loadModel('Users');
		$this->loadModel('Logcoupons');
		$this->loadModel('Sellercoupons');
		$this->loadModel('Sitesettings');
		$this->loadModel('Carts');
		$this->loadModel('Taxes');
		$this->loadModel('Shops');
		$this->loadModel('Shipings');
		$shippingbyseller = array();
		$shippingbyitem = array();

		$Sitesettings = TableRegistry::get('Sitesettings')->find('all')->first();

		if (SITE_URL == $_SESSION['media_url']) {
			$img_path = $_SESSION['media_url'];
		} else {
			$img_path = $_SESSION['media_url'];
		}
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->toArray();
		$usercreditTotal = $this->Userinvitecredits->find()->where(['user_id' => $userId])->all();
		foreach ($usercreditTotal as $usercreditTotals) {
			$totalCredit += $usercreditTotals['credit_amount'];
		}

		$siteChanges = $setngs[0]['site_changes'];
		$siteChanges = json_decode($siteChanges, true);
		$defaultcurrency_data = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();
		$userModel = $this->Users->find()->where(['id' => $userId])->first();
		if ($userModel['currencyid'] == 0 || $userModel['currencyid'] == "" || $userModel['currencyid'] == $defaultcurrency_data['id']) {
			$currency_data = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();
			$cur = $currency_data['price'];
		} else {
			$currency_data = $this->Forexrates->find()->where(['id' => $userModel['currencyid']])->first();
			$cur = $currency_data['price'];
		}

		if ($shippingId == 0) {
			$defaultAddress = $userModel['defaultshipping'];
			$shipping_address = $this->Tempaddresses->find()->where(['shippingid' => $defaultAddress])->andWhere(['userid' => $userId])->first();

		} else {
			$defaultAddress = $_POST['shipping_id'];
			$shipping_address = $this->Tempaddresses->find()->where(['shippingid' => $defaultAddress])->first();

		}

		/** ADD TO CART */
		if ($itemIds == 0) 
		{
			$i = 1;
			$cartModel = $this->Carts->find()->where(['user_id' => $userId])->andWhere(['payment_status' => 'progress'])->all();
			$c = count($cartModel);
			$shoprooms = array();
			$itemRooms = array();

			//echo '<pre>'; print_r($cartModel); die;
			foreach ($cartModel as $cartModels) 
			{
				$temp[] = '';
				$itemIds1 = $cartModels['item_id'];
				$itemQuantity += $cartModels['quantity'];
				$itemId[] = $cartModels['item_id'];
				$item_datas = TableRegistry::get('Items')->find()->contain('Forexrates')->where(['Items.id' => $itemIds1])->andWhere(['Items.status' => 'publish'])->first();
				$cod_status[] = $item_datas['cod'];
				$catids[] = $item_datas['category_id'];
				$itemuserids[] = $item_datas['user_id'];
				if (count($item_datas) == 0) {
					echo '{"status":"false","message":"One of your item not available"}';
					die;
				}
				$size = $cartModels['size_options'];
				if ($size == "" || $size == "No size") {
					$itemPrice = $item_datas['price'];
					$itemQuantity = $item_datas['quantity'];
					if($cartModels['quantity'] > $itemQuantity)
					{
						$no_quantity_available="Requested Quantity Not Available"; // added cart quantity sold out.
						if($itemQuantity==0 || $itemQuantity=="" || $itemQuantity==null)
						{
							$no_quantity_available="Out Of Stock";
						}
						echo '{"status":"false","item_id":"'.$cartModels['item_id'].'" ,"message":"'.$no_quantity_available.'"}';
						die;
					}
					//$price = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $item_datas['price']) * $cartModels['quantity'];

					$price = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $item_datas['price']);
					
					/*
					if ($item_datas['dailydeal'] == 'yes' && strtotime($item_datas['dealdate']) == $today) {
						$dailydealdiscount = $item_datas['discount'];
						$unitPriceConvert = number_format((float)$price, 2, '.', '');
						$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);
						if ($daily_price != "") {
							$price = number_format((float)$daily_price, 2, '.', '');
						}
					}
					*/
					$tdy = strtotime(date('Y-m-d'));
					if (strtotime($item_datas['dealdate']) == $tdy && $item_datas['discount_type'] == 'daily') {
						$dailydealdiscount = $item_datas['discount'];
						$unitPriceConvert = number_format((float)$price, 2, '.', '');
						$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);
						if ($daily_price != "") {
							$price = number_format((float)$daily_price, 2, '.', '');
						}
					} elseif($item_datas['discount_type'] == 'regular') {
						$dailydealdiscount = $item_datas['discount'];
						$unitPriceConvert = number_format((float)$price, 2, '.', '');
						$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);
						if ($daily_price != "") {
							$price = number_format((float)$daily_price, 2, '.', '');
						}
					}else{
						$price = number_format((float)$item_datas['price'], 2, '.', '');
					}

					$price = $price * $cartModels['quantity'];

					$pricetot += $price;
					$selleramt[$item_datas['shop_id']] += $price;
					$selleramtuser[$item_datas['user_id']] += $price;
					$selleramtcategory[$item_datas['user_id']][$item_datas['category_id']]+= $price;
					$selleramtitem[$item_datas['user_id']][$item_datas['id']]+= $price;
					$taxByItem[$cartModels['id']] = round($price, 2);

				} else {
					$sizeoptions = $item_datas['size_options'];
					$sizes = json_decode($sizeoptions, true);
					if (!empty($sizes)) {
						$sizeoptions = $item_datas['size_options'];
						$sizes = json_decode($sizeoptions, true);
						$itemPrice = $sizes['price'][$size];
						$itemQuantity = $sizes['unit'][$size];
						if($cartModels['quantity'] > $itemQuantity)
						{
							$no_quantity_available="Only".$itemQuantity."available";
							if($itemQuantity==0 || $itemQuantity=="" || $itemQuantity==null)
							{
								$no_quantity_available="Out Of Stock";
							}
							echo '{"status":"false","item_id":"'.$cartModels['item_id'].'",message":"'.$no_quantity_available.'"}';
							die;
						}
						//$price = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $sizes['price'][$size]) * $cartModels['quantity'];
						$price = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $sizes['price'][$size]);
						$tdy = strtotime(date('Y-m-d'));
						if (strtotime($item_datas['dealdate']) == $tdy && $item_datas['discount_type'] == 'daily') {
							$dailydealdiscount = $item_datas['discount'];
							$unitPriceConvert = number_format((float)$price, 2, '.', '');
							$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);
							if ($daily_price != "") {
								$price = number_format((float)$daily_price, 2, '.', '');
							}
						} elseif($item_datas['discount_type'] == 'regular') {
							$dailydealdiscount = $item_datas['discount'];
							$unitPriceConvert = number_format((float)$price, 2, '.', '');
							$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);
							if ($daily_price != "") {
								$price = number_format((float)$daily_price, 2, '.', '');
							}
						}else{
							$price = number_format((float)$item_datas['price'], 2, '.', '');
						}
						$price = $price*$cartModels['quantity'];
						$pricetot += $price;
						$selleramt[$item_datas['shop_id']] += $price;
						$selleramtuser[$item_datas['user_id']] += $price;
						$selleramtcategory[$item_datas['user_id']][$item_datas['category_id']]+= $price;
						$selleramtitem[$item_datas['user_id']][$item_datas['id']]+= $price;
						$taxByItem[$cartModels['id']] = round($price, 2);

					}
				}
				$temp[$item_datas['shop_id']][$i] = $price . ',' . $item_datas['id'];

				
				if (!in_array($item_datas['shop_id'], $shoprooms)) {
					array_push($shoprooms, $item_datas['shop_id']);
				}

				$shiping = $this->Shipings->find()->where(['item_id' => $item_datas['id']])->andWhere(['country_id' => $shipping_address['countrycode']])->first();

				if (count($shiping) == 0) {
					$shiping = $this->Shipings->find()->where(['item_id' => $item_datas['id']])->andWhere(['country_id' => 0])->first();
				}
				
				$shipingprice = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $shiping['primary_cost']);
				$shipingprice = round($shipingprice, 2);

				
				$shippingbyitem[$item_datas['id']] = $shipingprice;

				/* shipping price once for same size */
				if (!in_array($item_datas['id'], $itemRooms)) {
					array_push($itemRooms, $item_datas['id']);
					$shippingbyseller[$item_datas['shop_id']] += $shipingprice;
				}
				
				$shop_data = $this->Shops->find()->where(['id' => $item_datas['shop_id']])->first();
				$postalcode = json_decode($shop_data['postalcodes'], true);
				if (in_array($shipping_address['zipcode'], $postalcode)) {
					$shippingbyseller[$item_datas['shop_id']] = 0;
					$shippingbyitem[$item_datas['id']] = 0;
				}

				if ($i == $c) {
					for ($j = 0; $j < count($shoprooms); $j++) {
						$shop_det = $this->Shops->find()->where(['id' => $shoprooms[$j]])->first();
						$shopCurrencyDetails = $this->Forexrates->find()->where(['currency_code' => $shop_det['currency']])->first();
						$amt = $selleramt[$shoprooms[$j]];
						$freeamt = $this->Currency->conversion($shopCurrencyDetails['price'], $cur, $shop_det['freeamt']);
						if ($amt >= $freeamt && $freeamt > 0) {
							$shippingbyseller[$shoprooms[$j]] = 0;
						}
					}
				}

				$this->loadModel('Commissions');
				$commiDetails = $this->Commissions->find()->where(['active' => '1'])->all();
				$commissionItemConvert = $this->Currency->conversion($cur, $defaultcurrency_data['price'], $price);
				foreach ($commiDetails as $commi) {
					$min_val = $commi['min_value'];
					$max_val = $commi['max_value'];
					if ($commissionItemConvert >= $min_val && $commissionItemConvert <= $max_val) {
						if ($commi['type'] == '%') {
							$amount = (floatval($commissionItemConvert) / 100) * ($commi['amount']);
							$commiItemTotalPrice += $amount;
						}
					}
				}
				if (count($commiDetails) < 0) {
					$commission_amount = (floatval($price) / 100) * $Sitesettings['credit_percentage'];
					$commiItemTotalPrice = $commission_amount;
				}

				$i++;

			}

			if (in_array("no", $cod_status)) {
				$cod = "disable";
			} else {
				$cod = "enable";
			}
			
			if ($giftNo != "") {
				$gift_data = $this->Giftcards->find()->where(['giftcard_key' => $giftNo])->where(['reciptent_email' => $userModel['email']])->first();
				if (count($gift_data) == 0) {
					echo '{"status":"false","message":"Gift card voucher not valid"}';
					die;
				} elseif ($gift_data['avail_amount'] == null || $gift_data['avail_amount'] == 0) {
					echo '{"status":"false","message":"Gift card already fully used"}';
					die;
				}
			}

			$tax_datas = $this->Taxes->find()->where(['countryid' => $shipping_address['countrycode']])->andWhere(['status' => 'enable'])->all();
			foreach ($tax_datas as $taxes) {
				$tax_cost += $taxes['percentage'];
			}
			
			$tax_amount = ($tax_cost * $pricetot) / 100;
			
			foreach ($taxByItem as $taxKey => $itemPrice) {
				$taxByItem[$taxKey] = round(($tax_cost * $itemPrice) / 100, 2);
			}

			for ($sellerItem = 0; $sellerItem < count($itemuserids); $sellerItem++) {
				$couponCount = $this->Sellercoupons->find()->where(['couponcode' => $couponCode])->andWhere(['sellerid' => 
					$itemuserids[$sellerItem]])->count();
				if($couponCount>0){
					$coupon_datas = $this->Sellercoupons->find()->where(['couponcode' => $couponCode])->andWhere(['sellerid' => $itemuserids[$sellerItem]])->first();	
				}
			}

			/* Coupon Code */
			if ($couponCode) {
				$coupon_not_valid_message = '{"status":"false","message":"Sorry,this coupon is not valid"}';
				if (empty($coupon_datas)) {
					echo $coupon_not_valid_message;
					die;
				}
				else
				{  
					$couponid = $coupon_datas['id'];
					$couponuse = $coupon_datas['one_time_use'];
					$coupondiscountperc = $coupon_datas['couponpercentage'];
					if ($couponuse == "yes") {
						$couponlogs = $this->Logcoupons->find()->where(['coupon_id' => $couponid])->andWhere(['user_id' => $userId])->count();
					}
					$start_date = $coupon_datas['validfrom'];
					$last_date = $coupon_datas['validto'];
					$range = $coupon_datas['remainrange'];
					$sellerid = $coupon_datas['sellerid'];
					$type = $coupon_datas['type'];
					$sourceid = $coupon_datas['sourceid'];
					$today_date = time();
					$today_date = (is_string($today_date) ? strtotime($today_date) : $today_date);
					$today_date = strtotime(date('m/d/Y',$today_date));
					$start_date = (is_string($start_date) ? strtotime($start_date) : $start_date);
					$last_date = (is_string($last_date) ? strtotime($last_date) : $last_date);
					if ($range == 0 || $range < 0) {
						echo $coupon_not_valid_message;
						die;
					} else if ($start_date <= $today_date && $last_date >= $today_date && $range >= 1) {
						if ($type == "item" || $type == "facebook") {
							if (in_array($sellerid, $itemuserids)) {
								if (!in_array($sourceid, $itemId)) {
									echo $coupon_not_valid_message;
									die;
								}
								else{
									$coupondiscount = $selleramtitem[$sellerid][$sourceid]* ($coupondiscountperc / 100);
								}
							} else {
								echo $coupon_not_valid_message;
								die;
							}
						} else if ($type == "category") {
							if (in_array($sellerid, $itemuserids)) {
								if (!in_array($sourceid, $catids)) {
									echo $coupon_not_valid_message;
									die;
								}
								else{
									$coupondiscount = $selleramtcategory[$sellerid][$sourceid]* ($coupondiscountperc / 100);
								}
							} else {
								echo $coupon_not_valid_message;
								die;
							}
						} elseif ($type == "cart") {
							if (!in_array($sellerid, $itemuserids)) {
								echo $coupon_not_valid_message;
								die;
							}
							else{
								$coupondiscount = $selleramtuser[$sellerid]* ($coupondiscountperc / 100);
							}
						} else {
							echo $coupon_not_valid_message;
							die;
						}
					} else {
						echo '{"status":"false","message":"Sorry,this coupon is expired"}';
						die;
					}

				}
				
			}

		}
		
		/** Buynow starts */
		else 
		{
			$itemId[] = $_POST['item_id'];
			$itemQuantity = $_POST['quantity'];
			$size = $_POST['size'];
			$item_datas = TableRegistry::get('Items')->find()->contain('Forexrates')->where(['Items.id IN' => $itemId])->andWhere(['Items.status' => 'publish'])->first();
			$shop_data = $this->Shops->find()->where(['id' => $item_datas['shop_id']])->first();
			$shopCurrencyDetails = $this->Forexrates->find()->where(['currency_code' => $shop_data['currency']])->first();
			$freeamt = $this->Currency->conversion($shopCurrencyDetails['price'], $cur, $shop_data['freeamt']);
			if (count($item_datas) == 0) {
				echo '{"status":"false","message":"One of your item not available"}';
				die;
			}
			$catids[] = $item_datas['category_id'];
			$itemuserids[] = $item_datas['user_id'];
			if ($size == "" || $size == "No size") {
				//echo 'size m'; die;
				$price = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $item_datas['price']);
				/*
				if ($item_datas['dailydeal'] == 'yes' && strtotime($item_datas['dealdate']) == $today) {
					$dailydealdiscount = $item_datas['discount'];
					$unitPriceConvert = number_format((float)$price, 2, '.', '');
					$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);
					if ($daily_price != "") {
						$price = number_format((float)$daily_price, 2, '.', '');
					}
				}
				*/
				$tdy = strtotime(date('Y-m-d'));
				if ($item_datas['discount_type'] == 'daily' && strtotime($item_datas['dealdate']) == $tdy) {
						$dailydealdiscount = $item_datas['discount'];
						$unitPriceConvert = number_format((float)$price, 2, '.', '');
						$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);
						if ($daily_price != "") {
							//$pricetot += number_format((float)$daily_price, 2, '.', '');
						}
						$price = $daily_price;
					} elseif($item_datas['discount_type'] == 'regular') {
					$dailydealdiscount = $item_datas['discount'];
					$unitPriceConvert = number_format((float)$price, 2, '.', '');
					$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);
					if ($daily_price != "") {
						//$pricetot += number_format((float)$daily_price, 2, '.', '');
					}
						$price = number_format((float)$daily_price, 2, '.', '');
					}else{
						$price = $price;
					}


				$pricetot += $price * $itemQuantity;
			} else {
				//echo 'size n'; die;
				$sizeoptions = $item_datas['size_options'];
				$sizes = json_decode($sizeoptions, true);

				//echo '<pre>'; print_r($sizes); die;
				if (!empty($sizes)) {
					$sizeoptions = $item_datas['size_options'];
					$sizes = json_decode($sizeoptions, true);

					$price = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $sizes['price'][$size]);
					
					/*if ($item_datas['dailydeal'] == 'yes' && strtotime($item_datas['dealdate']) == $today) {
						$dailydealdiscount = $item_datas['discount'];
						$unitPriceConvert = number_format((float)$price, 2, '.', '');
						$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);
						if ($daily_price != "") {
							$price = number_format((float)$daily_price, 2, '.', '');
						}
					}*/
					
					$tdy = strtotime(date('Y-m-d'));
					if ($item_datas['discount_type'] == 'daily' && strtotime($item_datas['dealdate']) == $tdy) {
						$dailydealdiscount = $item_datas['discount'];
						$unitPriceConvert = number_format((float)$price, 2, '.', '');
						$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);
						if ($daily_price != "") {
							//$pricetot += number_format((float)$daily_price, 2, '.', '');
						}
						$price = $daily_price;
					} elseif($item_datas['discount_type'] == 'regular') {
					$dailydealdiscount = $item_datas['discount'];
					$unitPriceConvert = number_format((float)$price, 2, '.', '');
					$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);
					if ($daily_price != "") {
						//$pricetot += number_format((float)$daily_price, 2, '.', '');
					}
						$price = number_format((float)$daily_price, 2, '.', '');
					}else{
						$price = $price;
					}
					$pricetot += $price * $itemQuantity;

				}
			}

			//echo $pricetot; die;

			$taxByItem[$item_datas['id']] = round($price, 2);
			if ($item_datas['cod'] == "no") {
				$cod = "disable";
			} else {
				$cod = "enable";
			}

			$shippingbyitem[$item_datas['id']] = 0;
			$shippingbyseller[$item_datas['shop_id']] = 0;

			$shiping = $this->Shipings->find()->where(['item_id' => $item_datas['id']])->andWhere(['country_id' => $shipping_address['countrycode']])->first();
			if (count($shiping) == 0) {
				$shiping = $this->Shipings->find()->where(['item_id' => $item_datas['id']])->andWhere(['country_id' => 0])->first();

			}
			$postalcode = json_decode($shop_data['postalcodes'], true);
			if (in_array($shipping_address['zipcode'], $postalcode)) {
				$shipingprice = 0;
			} elseif ($pricetot >= $freeamt && $shop_data['pricefree'] == 'yes') {
				$shipingprice = 0;
			} else {
				$shipingprice = $this->Currency->conversion($item_datas['forexrate']['price'], $cur, $shiping['primary_cost']);
			}
					//echo $shipingprice;
			$ship_cost += $shipingprice;

			$shippingbyitem[$item_datas['id']] = $shipingprice;
			$shippingbyseller[$item_datas['shop_id']] += $shipingprice;

			$this->loadModel('Commissions');
			$commiDetails = $this->Commissions->find()->where(['active' => '1'])->all();
			$commissionItemConvert = $this->Currency->conversion($cur, $defaultcurrency_data['price'], $pricetot);
			foreach ($commiDetails as $commi) {
				$min_val = $commi['min_value'];
				$max_val = $commi['max_value'];
				if ($commissionItemConvert >= $min_val && $commissionItemConvert <= $max_val) {
					if ($commi['type'] == '%') {
						$amount = (floatval($commissionItemConvert) / 100) * ($commi['amount']);
						$commiItemTotalPrice += $amount;
					}
				}
			}
			if (count($commiDetails) < 0) {
				$commission_amount = (floatval($pricetot) / 100) * $Sitesettings['credit_percentage'];
				$commiItemTotalPrice = $commission_amount;
			}

			if ($giftNo != "") {
				$gift_data = $this->Giftcards->find()->where(['giftcard_key' => $giftNo])->where(['reciptent_email' => $userModel['email']])->first();
				if (count($gift_data) == 0) {
					echo '{"status":"false","message":"Gift card voucher not valid"}';
					die;
				} elseif ($gift_data['avail_amount'] == null || $gift_data['avail_amount'] == 0) {
					echo '{"status":"false","message":"Gift card already fully used"}';
					die;
				}
			}

			$tax_datas = $this->Taxes->find()->where(['countryid' => $shipping_address['countrycode']])->andWhere(['status' => 'enable'])->all();
			foreach ($tax_datas as $taxes) {
				$tax_cost += $taxes['percentage'];
			}

			$tax_amount = ($tax_cost * $pricetot) / 100;
			foreach ($taxByItem as $taxKey => $itemPrice) {
				$taxByItem[$taxKey] = round(($tax_cost * $itemPrice) / 100, 2);
			}

			$coupon_datas = $this->Sellercoupons->find()->where(['couponcode' => $couponCode])->andWhere(['sellerid' => 
				$item_datas['user_id']])->first();

			/* Coupon Code */
			if ($couponCode) {
				$coupon_not_valid_message = '{"status":"false","message":"Sorry,this coupon is not valid"}';
				if (empty($coupon_datas)) {
					echo $coupon_not_valid_message;
					die;
				}
				else
				{  
					//echo $pricetot; die;
					$couponid = $coupon_datas['id'];
					$couponuse = $coupon_datas['one_time_use'];
					$coupondiscountperc = $coupon_datas['couponpercentage'];
					$coupondiscount = $pricetot * ($coupondiscountperc / 100);
					if ($couponuse == "yes") {
						$couponlogs = $this->Logcoupons->find()->where(['coupon_id' => $couponid])->andWhere(['user_id' => $userId])->count();
					}
					$start_date = $coupon_datas['validfrom'];
					$last_date = $coupon_datas['validto'];
					$range = $coupon_datas['remainrange'];
					$sellerid = $coupon_datas['sellerid'];
					$type = $coupon_datas['type'];
					$sourceid = $coupon_datas['sourceid'];
					$today_date = time();
					$today_date = (is_string($today_date) ? strtotime($today_date) : $today_date);

					$today_date = strtotime(date('m/d/Y',$today_date));

					$start_date = (is_string($start_date) ? strtotime($start_date) : $start_date);
					$last_date = (is_string($last_date) ? strtotime($last_date) : $last_date);

					
					if ($range == 0 || $range < 0) {
						echo $coupon_not_valid_message;
						die;
					} else if ($start_date <= $today_date && 
								$today_date <= $last_date && 
								$range >= 1) {
						if ($type == "item" || $type == "facebook") {
							if (in_array($sellerid, $itemuserids)) {
								if (!in_array($sourceid, $itemId)) {
									echo $coupon_not_valid_message;
									die;
								}
							} else {
								echo $coupon_not_valid_message;
								die;
							}
						} else if ($type == "category") {
							if (in_array($sellerid, $itemuserids)) {
								if (!in_array($sourceid, $catids)) {
									echo $coupon_not_valid_message;
									die;
								}
							} else {
								echo $coupon_not_valid_message;
								die;
							}
						} elseif ($type == "cart") {
							if (!in_array($sellerid, $itemuserids)) {
								echo $coupon_not_valid_message;
								die;
							}
						} else {
							echo $coupon_not_valid_message;
							die;
						}
					} else {
						echo '{"status":"false","message":"Sorry,this coupon is expired"}';
						die;
					}

				}
				
			}
		}
		/** Buynow ends */

		$ship_cost = array_sum($shippingbyseller);
		$resultArray = array();
		if ($itemQuantity == null)
			$itemQuantity = 0;
		if ($commiItemTotalPrice == null)
			$commiItemTotalPrice = 0;
		if ($creditAmount == null)
			$creditAmount = 0;
		if ($gift_data['avail_amount'] == null)
			$gift_data['avail_amount'] = 0;
		if ($coupondiscount == null)
			$coupondiscount = 0;
		if ($pricetot == null)
			$pricetot = 0;
		$resultArray['item_count'] = $itemQuantity;
		if ($userModel['credit_total'] == null)
			$resultArray['total_credit'] = 0;
		else {
			$creditprice = $this->Currency->conversion($defaultcurrency_data['price'], $cur, $userModel['credit_total'] + $creditAmtByAdmin);
			$resultArray['total_credit'] = $creditprice;
		}
		$resultArray['max_credit_usable'] = $this->Currency->conversion($defaultcurrency_data['price'], $cur, $commiItemTotalPrice);
		$resultArray['credit_used'] = $creditAmount;

		/** GIFTCARD DISCOUNT */
		if ($giftNo != "") {
			$forexrateModel = $this->Forexrates->find()->where(['id' => $gift_data['currencyid']])->first();
			$giftcardrate = $forexrateModel['price'];
			$resultArray['gift_amount'] = $this->Currency->conversion($giftcardrate, $cur, $gift_data['avail_amount']);
		} else {
			$resultArray['gift_amount'] = 0;
		}

		$resultArray['cod'] = $cod;
		$resultArray['currency_code'] = $currency_data['currency_code'];
		$resultArray['currency'] = $currency_data['currency_symbol'];
		$resultArray['coupon_discount'] = round($coupondiscount, 2);
		$resultArray['item_total'] = round($pricetot, 2);
		$resultArray['shipping_price'] = round($ship_cost, 2);
		$resultArray['tax'] = round($tax_amount, 2);
		$resultArray['shippingbyitem'] = $shippingbyitem;
		$resultArray['taxbyitem'] = $taxByItem;
		$resultArray['shippingbyseller'] = $shippingbyseller;


		$grand_total = round($tax_amount + $ship_cost + $pricetot - $creditAmount, 2);

		//echo $coupondiscount; die;
		if ($coupondiscount != 0) {
			$resultArray['grand_total'] = round($grand_total - $coupondiscount, 2);
		} elseif ($_POST['gift_no'] != "") {
			if ($gift_data['avail_amount'] < $grand_total) {
				$resultArray['grand_total'] = round($grand_total - $this->Currency->conversion($giftcardrate, $cur, $gift_data['avail_amount']), 2);
			} else {
				$resultArray['grand_total'] = 0;
			}
		} else {
			$resultArray['grand_total'] = $grand_total;
		}

		//echo $resultArray['grand_total']; die;

		if (empty($resultArray)) {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		} else {
			echo '{"status":"true","result":' . json_encode($resultArray) . '}';
			die;
		}
	}

	function shareGroupgift()
	{

		$this->loadModel('Groupgiftuserdetails');
		$this->loadModel('Logs');
		$this->loadModel('Users');

		$userId = $_POST['user_id'];
		$contributorId = $_POST['contributor_id'];
		$giftId = $_POST['gift_id'];
		if (!empty($_POST['contributor_id'])) {
			$contributor_id = explode(",", $_POST['contributor_id']);
			foreach ($contributor_id as $contributor_ids) {
				$contributor_id1[] = $contributor_ids;
			}
		}

		$gift_detail = $this->Groupgiftuserdetails->find()->where(['id' => $giftId])->andWhere(['balance_amt >' => 0])->first();
		if (count($gift_detail) == 0) {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		}
		$item_id = $gift_detail['item_id'];
		$userdatasall = $this->Users->find()->where(['id IN' => $contributor_id1])->all();
		foreach ($userdatasall as $userdatasall) {
			if ($userId != $userdatasall['id']) {
				$this->loadModel('Userdevices');
				$this->loadModel('Users');
				$notifyto = $userdatasall['id'];
				$notificationSettings = $this->Users->find()->where(['id' => $notifyto])->first();
				$notificationSettings = json_decode($notificationSettings['push_notifications'], true);
				if ($userId != $notifyto) {
					$loguser = $this->Users->find()->where(['id' => $userId])->toArray();
					$logusername = $loguser[0]['username'];
					$logusernameurl = $loguser[0]['username_url'];

					$image['image'] = $loguser[0]['profile_image'];
					$image['link'] = SITE_URL . "people/" . $logusernameurl;

					$loguserimage = json_encode($image);
					$loguserlink = "<a href='" . SITE_URL . "people/" . $logusernameurl . "'>" . $logusername . "</a>";

					$giftcardlink = "<a href='" . SITE_URL . "gifts/" . $giftId . "'>#" . $giftId . "</a>";
					$notifymsg = $loguserlink . " shared a groupgift to you. Groupgift Id:" ."-___-" .$giftcardlink;
					$logdetails = $this->addlog('groupgift', $userId, $notifyto, $giftId, $notifymsg, null, $loguserimage, $item_id);

					/* Push notifications */
					$userddetts = $this->Userdevices->findAllByUser_id($notifyto)->all();
					if (count($userddetts) > 0) {
						foreach ($userddetts as $userdet) {
							$deviceTToken = $userdet['deviceToken'];
							$badge = $userdet['badge'];
							$badge += 1;
							$query = TableRegistry::get('Userdevices')->query();
							$query->update()->set(['badge' => $badge])->where(['deviceToken' => $deviceTToken])->execute();

							if (isset($deviceTToken)) {
								$user_profile_image = $loguser[0]['profile_image'];
								if ($user_profile_image == "")
									$user_profile_image = "usrimg.jpg";
								$pushMessage['type'] = 'group_gift';
								$pushMessage['user_id'] = $loguser[0]['id'];
								$pushMessage['user_name'] = $loguser[0]['username'];
								$pushMessage['user_image'] = $user_profile_image;
								$user_detail = TableRegistry::get('Users')->find()->where(['id' => $notifyto])->first();
								I18n::locale($user_detail['languagecode']);
								$pushMessage['message'] = $loguser[0]['username'];
								$pushMessage['message'].= __d('user', " shared a groupgift to you");
								$messages = json_encode($pushMessage);
								$this->pushnot($deviceTToken, $messages, $badge);
							}
						}
					}
				}
				
			}
			echo '{"status":"true","message":"Shared successfully"}';
			die;
		}
	}

	/* Place Order */
	function Createorder()
	{
		$userId = $_POST['user_id'];
		$itemId = $_POST['item_id'];
		$shippingId = $_POST['shipping_id'];
		$paymentType = $_POST['payment_type'];
		$today = strtotime(date("Y-m-d"));
		$itemTotal = $_POST['item_total'];
		$shippingPrice = $_POST['shipping_price'];
		$taxamt = $_POST['tax'];
		$grandTotal = $_POST['grand_total'];
		$couponId = $_POST['coupon_code'];
		$couponDiscount = $_POST['coupon_discount'];
		$userEnterCreditAmt = $_POST['credit_used'];
		$giftAmount = $_POST['gift_amount'];
		$giftId = $_POST['gift_id'];
		$nonce = $_POST['pay_nonce'];
		$size = $_POST['size'];
		$quantity = $_POST['quantity'];
		$shippingbyitem = json_decode($_POST['shippingbyitem'], true);
		$shippingbyseller = json_decode($_POST['shippingbyseller'], true);
		$taxbyitem = json_decode($_POST['taxbyitem'], true);
		$this->loadModel('Users');
		$this->loadModel('Tempaddresses');
		$this->loadModel('Shippingaddresses');
		$this->loadModel('Sitesettings');
		$this->loadModel('Logcoupons');
		$this->loadModel('Items');
		$this->loadModel('Carts');
		$this->loadModel('Orders');
		$this->loadModel('Order_items');
		$this->loadModel('Userinvitecredits');
		$this->loadModel('Shipings');
		$this->loadModel('Sellercoupons');
		$this->loadModel('Giftcards');
		$this->loadModel('Shops');
		$this->loadModel('Invoices');
		$this->loadModel('Invoiceorders');
		$this->loadModel('Taxes');
		$setngs = $this->Sitesettings->find()->where(['id' => 1])->first();
		$siteChanges = json_decode($setngs['site_changes'], true);
		$creditAmtByAdmin = $siteChanges['credit_amount'];
		$userModel = $this->Users->find()->where(['id' => $userId])->first();
		$defaultcurrency_data = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();
		$Sitesettings = TableRegistry::get('Sitesettings')->find('all')->first();
		if ($userModel['currencyid'] == 0 || $userModel['currencyid'] == "") {
			$currency_data = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();
			$currency = $currency_data['currency_symbol'];
			$currency_code = $currency_data['currency_code'];
			$cur = 1;
		} else {
			$currency_data = $this->Forexrates->find()->where(['id' => $userModel['currencyid']])->first();
			$currency = $currency_data['currency_symbol'];
			$currency_code = $currency_data['currency_code'];
			$cur = $currency_data['price'];
		}

		if ($itemId == 0) {
			$cartModel = $this->Carts->find()->where(['user_id' => $userId])->andWhere(['payment_status' => 'progress'])->all();
			if (count($cartModel->toArray()) == 0) {
				echo '{"status":"false","message":"Something went to be wrong,Please try again x`."}';
				die;
			}
			foreach ($cartModel as $cartModels) {
				$itemIds[] = $cartModels['id'];
				$cartItems[$cartModels['id']]=$cartModels['item_id'];
			}
		} else{
			$itemIds[] = $itemId;
		}			

		if ($paymentType == "braintree" || $paymentType == "giftcard") {
			if ($grandTotal > 0) {
				include_once(WWW_ROOT . 'braintree/lib/Braintree.php');
				$paystatus = $setngs['braintree_setting'];
				$paystatus = json_decode($paystatus, true);
				foreach ($paystatus as $key => $value) {
					if ($key == $currency_code) {
						$merchant_account_id = $value['merchant_account_id'];
					}
				}
				$merchantid_settings = $setngs['merchantid_setting'];
				$merchantid_settings = json_decode($merchantid_settings, true);
				$params = array(
					"testmode" => $merchantid_settings['type'],
					"merchantid" => $merchantid_settings['merchant_id'],
					"publickey" => $merchantid_settings['public_key'],
					"privatekey" => $merchantid_settings['private_key'],
				);

				if ($params['testmode'] == "sandbox") {
					\Braintree_Configuration::environment('sandbox');
				} else {
					\Braintree_Configuration::environment('production');
				}

				\Braintree_Configuration::merchantId($params["merchantid"]);
				\Braintree_Configuration::publicKey($params["publickey"]);
				\Braintree_Configuration::privateKey($params["privatekey"]);
				$user_detls = $this->Users->find()->where(['id' => $userId])->first();

				if ($user_detls['customer_id'] == "") {

					$result1 = \Braintree_Customer::create([
						'firstName' => $user_detls['first_name'],
						'lastName' => $user_detls['last_name'],
						'paymentMethodNonce' => $nonce
					]);

					$customer_id = $result1->customer->id;
					$result = \Braintree_Transaction::sale(
						[
							'paymentMethodToken' => $result1->customer->paymentMethods[0]->token,
							'amount' => $grandTotal,
							'merchantAccountId' => $merchant_account_id,
							'options' => [
								'submitForSettlement' => true
							]
						]
					);

				} else {

					$customer_id = $user_detls['customer_id'];
					$result = \Braintree_Transaction::sale([
						'amount' => $grandTotal,
						'merchantAccountId' => $merchant_account_id,
						'paymentMethodNonce' => $nonce,
						'options' => [
							'submitForSettlement' => true
						]
					]);
				}
			}
		}
		
		if ($paymentType == "braintree" || $paymentType == "giftcard" || $paymentType == "cod" || $paymentType == "credit") 
		{

			if ($user_detls['customer_id'] == "") {
				$userstable = TableRegistry::get('Users');
				$usersquery = $userstable->query();
				$usersquery->update()
				->set(['customer_id' => $customer_id])
				->where(['id' => $userId])
				->execute();
			}

			$users = $this->Users->find()->where(['id' => $userId])->first();
			$shareData = json_decode($users['share_status'], true);
			$currentTime = time();
			$count = 0;
			if (empty($shareData)) {
				$shareData[$count][$currentTime] = 0;
				$shareData[$count]['amount'] = 0;
				$shareData = json_encode($shareData);
			} else {
				$count = count($shareData);
				$shareData[$count][$currentTime] = 0;
				$shareData[$count]['amount'] = 0;
				$shareData = json_encode($shareData);
			}
			$users->share_status = $shareData;
			$this->Users->save($users);
			if ($shippingId == 0) {
				$tempShippingModel = $this->Tempaddresses->find()->where(['shippingid' => $users['defaultshipping']])->first();
			} else {
				$tempShippingModel = $this->Tempaddresses->find()->where(['shippingid' => $shippingId])->first();
			}
			$shippingaddressesModel = $this->Shippingaddresses->find('all', array('conditions' => array(
				'userid' => $tempShippingModel['userid'],
				'nickname' => $tempShippingModel['nickname'],
				'name' => $tempShippingModel['name'],
				'address1' => $tempShippingModel['address1'],
				'address2' => $tempShippingModel['address2'],
				'city' => $tempShippingModel['city'],
				'state' => $tempShippingModel['state'],
				'country' => $tempShippingModel['country'],
				'zipcode' => $tempShippingModel['zipcode'],
				'phone' => $tempShippingModel['phone']
			)))->toArray();

			if (count($shippingaddressesModel) != 0) {
				$shippingId = $shippingaddressesModel[0]['shippingid'];
			} else {
				$shipping_data = $this->Shippingaddresses->newEntity();
				$shipping_data->userid = $tempShippingModel['userid'];
				$shipping_data->name = $tempShippingModel['name'];
				$shipping_data->nickname = $tempShippingModel['nickname'];
				$shipping_data->country = $tempShippingModel['country'];
				$shipping_data->state = $tempShippingModel['state'];
				$shipping_data->address1 = $tempShippingModel['address1'];
				$shipping_data->address2 = $tempShippingModel['address2'];
				$shipping_data->city = $tempShippingModel['city'];
				$shipping_data->zipcode = $tempShippingModel['zipcode'];
				$shipping_data->phone = $tempShippingModel['phone'];
				$shipping_data->countrycode = $tempShippingModel['countrycode'];
				$shipping_dataresult = $this->Shippingaddresses->save($shipping_data);
				$shippingId = $shipping_dataresult->shippingid;
			}
			if ($userEnterCreditAmt != 0) {
				$credit_amt_reduce = $userModel['credit_total'];
				$usedCreditInUSD = $userEnterCreditAmt * $cur;
				$credit_amt_reduce = $credit_amt_reduce - $usedCreditInUSD;
				$this->Users->updateAll(array('credit_total' => $credit_amt_reduce), array('id' => $userId));

			}
			$prevUserId = 0;
			$mercount = 0;
			$meritemcount = 0;
			
			foreach ($itemIds as $eachItem) {
				$cart_item_id=$eachItem;
				if ($itemId == 0) {
					$cart_item_id=$cartItems[$eachItem];
				}
				$itemModel = TableRegistry::get('Items')->find()->contain('Forexrates')->where(['Items.id' => $cart_item_id])->first();

				if ($prevUserId != $itemModel['user_id']) {
					$prevUserId = $itemModel['user_id'];
					$itemUsers[$mercount]['userid'] = $prevUserId;
					$prevcount = $mercount;
					$mercount++;
					$meritemcount = 0;
				}

				if ($_POST['quantity'] != "") {
					$itm_qty = $_POST['quantity'];
				} else {
					$cartData = TableRegistry::get('Carts')->find('all')->where(['id' => $eachItem])->order(['id' => 'DESC'])->first();
					$itm_qty = $cartData['quantity'];
				}

				if ($_POST['size'] != ""){
					$size = $_POST['size'];
				}
				else{	
					$size ="";
				}

				if ($itemId == 0){
					$cartData = TableRegistry::get('Carts')->find('all')->where(['id' => $eachItem])->order(['id' => 'DESC'])->first();
					$size = $cartData['size_options'];
				}

				

				if ($size != "") {
					$size_options = json_decode($itemModel['size_options'], true);			
					if ($size_options != "") {
						$price = $size_options['price'][$size];
						$dealPercentage = 0;
						if (($itemModel['discount_type'] == 'daily' && strtotime($itemModel['dealdate']) == $today) || ($itemModel['discount_type'] == 'regular')) {
							$dailydealdiscount = $itemModel['discount'];
							$unitPriceConvert = number_format((float)$size_options['price'][$size], 2, '.', '');
							$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);
							if ($daily_price != "") {
								$price = number_format((float)$daily_price, 2, '.', '');
							}
							$dealPercentage = $dailydealdiscount;
						}
						$price = $this->Currency->conversion($itemModel['forexrate']['price'], $cur, $price);
						$item_price = $price * $itm_qty;
						$itemunitprice = $price;
					}

				} else {
					$dealPercentage = 0;
					$price = $itemModel['price'];
					if (($itemModel['discount_type'] == 'daily' && strtotime($itemModel['dealdate']) == $today) || ($itemModel['discount_type'] == 'regular')) {
						$dailydealdiscount = $itemModel['discount'];
						$unitPriceConvert = number_format((float)$itemModel['price'], 2, '.', '');
						$daily_price = $unitPriceConvert * (1 - $dailydealdiscount / 100);
						if ($daily_price != "") {
							$price = number_format((float)$daily_price, 2, '.', '');
						}
						$dealPercentage = $dailydealdiscount;
					}
					$price = $this->Currency->conversion($itemModel['forexrate']['price'], $cur, $price);
					$item_price = $price * $itm_qty;
					$itemunitprice = $price;
				}

				//echo $itemunitprice; die;

				$shiping = $this->Shipings->find()->where(['item_id' => $itemModel['id']])->andWhere(['country_id' => $tempShippingModel['countrycode']])->first();
				$shipingprice = $this->Currency->conversion($itemModel['forexrate']['price'], $cur, $shiping['primary_cost']);
				$ship_cost = $shipingprice;
				$tax = $this->Taxes->find()->where(['countryid' => $tempShippingModel['country_id']])->andWhere(['status' => 'enable'])->first();
				$tax_cost = $tax['percentage'];
				$tax_amount = ($tax_cost * $pricetot) / 100;
				$itemUsers[$prevcount]['items'][$meritemcount]['itemid'] = $itemModel['id'];
				$itemUsers[$prevcount]['items'][$meritemcount]['shopid'] = $itemModel['shop_id'];
				$itemUsers[$prevcount]['items'][$meritemcount]['itemname'] = $itemModel['item_title'];
				$itemUsers[$prevcount]['items'][$meritemcount]['item_skucode'] = $itemModel['skuid'];
				$itemUsers[$prevcount]['items'][$meritemcount]['quantity'] = $itm_qty;
				$itemUsers[$prevcount]['items'][$meritemcount]['size'] = $size;
				$itemUsers[$prevcount]['items'][$meritemcount]['price'] = $item_price;
				$itemUsers[$prevcount]['items'][$meritemcount]['shipping_price'] = $ship_cost;
				if ($itemId == 0) {
					$itemUsers[$prevcount]['items'][$meritemcount]['tax_price'] = $taxbyitem[$eachItem];
					$itemUsers[$prevcount]['tax_price'] += $taxbyitem[$eachItem];
				}
				else{
					// /echo '<pre>'; print_r($taxbyitem); die;
					$itemUsers[$prevcount]['items'][$meritemcount]['tax_price'] = $taxbyitem[$itemModel['id']];
					$itemUsers[$prevcount]['tax_price'] += $taxbyitem[$itemModel['id']];
				}
				$itemUsers[$prevcount]['items'][$meritemcount]['unit_price'] = $itemunitprice;
				$itemUsers[$prevcount]['items'][$meritemcount]['dealPercentage'] = $dealPercentage;
				$itemUsers[$prevcount]['price'] += $item_price;
				$itemUsers[$prevcount]['shipping_price'] += $ship_cost;
				$meritemcount++;
			}
			$itemuserjson = json_encode($itemUsers);
			foreach ($itemUsers as $itemUser) 
			{
				$orderComission = 0;
				$totalcost = 0;
				$totalCostshipp = 0;
				$order_data = $this->Orders->newEntity();
				$order_data->userid = $userId;
				$order_data->merchant_id = $itemUser['userid'];
				$shopdatas = $this->Shops->find()->where(['user_id' => $itemUser['userid']])->first();
				$order_data->totalcost = 0;
				$order_data->totalCostshipp = 0;
				$order_data->tax = $itemUser['tax_price'];
				$order_data->orderdate = time();
				$order_data->shippingaddress = $shippingId;
				$order_data->coupon_id = $couponId;
				$order_data->currency = $currency_code;
				$order_data->totalCostshipp = $shippingbyseller[$shopdatas['id']];
				$order_data->status = "Pending";
				if ($paymentType == "braintree")
					$order_data->deliverytype = 'braintree';
				else if ($paymentType == "cod")
					$order_data->deliverytype = 'cod';
				else if ($paymentType == "credit")
					$order_data->deliverytype = 'credit';
				else
					$order_data->deliverytype = 'giftcard';
				$order_dataresult = $this->Orders->save($order_data);
				$orderId = $order_dataresult->orderid;
				if ($ordersId == "") {
					$ordersId .= "#" . $orderId;
				} else {
					$ordersId .= ", #" . $orderId;
				}
				$totalcost = 0;
				$tax_rate = 0;
				for ($j = 0; $j < count($itemUser['items']); $j++) 
				{
					//echo 'test'; die;
					$orderitem_data = $this->Order_items->newEntity();
					$orderitem_data->orderid = $orderId;
					$orderitem_data->itemid = $itemUser['items'][$j]['itemid'];
					$orderitem_data->itemname = $itemUser['items'][$j]['itemname'];
					$orderitem_data->item_size = $itemUser['items'][$j]['size'];
					$orderitem_data->itemprice = $itemUser['items'][$j]['price'];
					$orderitem_data->itemquantity = $itemUser['items'][$j]['quantity'];
					$orderitem_data->itemunitprice = $itemUser['items'][$j]['unit_price'];
					$itemShippingCost=$shippingbyitem[$itemUser['items'][$j]['itemid']];
					if($shippingbyseller[$itemUser['items'][$j]['shopid']] == 0){
						$itemShippingCost=0;
					}
					$orderitem_data->shippingprice =$itemShippingCost;
					$orderitem_data->tax = $itemUser['items'][$j]['tax_price']*$itemUser['items'][$j]['quantity'];

					//echo '<pre>'; print_r($itemUser['items'][$j]); die;
					$orderitem_data->dealPercentage = $itemUser['items'][$j]['dealPercentage'];


					$amount = $itemTotal + $shippingPrice + $taxamt;
					$totalcost += $itemUser['items'][$j]['price'];
					$itemTotalPrice = $itemUser['items'][$j]['price'];
					$tax_rate += ($itemUser['items'][$j]['tax_price']*$itemUser['items'][$j]['quantity']);
					//$tax_rate += $itemUser['items'][$j]['tax_price'];

					//echo $tax_rate; die;
					/* UPDATE ORDERS TABLE */
					$this->Orders->updateAll(array('totalcost' => $totalcost, 'tax' => $tax_rate), array('orderid' => $orderId));

					$totamo = $orderitem_data->itemprice + $itemShippingCost + $itemUser['items'][$j]['tax_price'];



					if ($_POST['gift_amount'] != "") {
						
						$orderitem_data->discountType = 'Giftcard Discount';
						$giftCardDetails = $this->Giftcards->find()->where(['giftcard_key' => $giftId])->first();
						$giftCurrencyDetails = $this->Forexrates->find()->where(['id' => $giftCardDetails['currencyid']])->first();
						$giftAmount = $giftCardDetails['avail_amount'];

						if ($totamo < $giftAmount) {
							$giftcard_discount_amt = $amount;
							$orderitem_data->discountAmount = $amount;
							$orderitem_data->giftamount = $totamo;
							$available = $giftAmount - $totamo;
							$available = $this->Currency->conversion($cur, $giftCurrencyDetails['price'], $available);
							$this->Giftcards->updateAll(array('avail_amount' => $available), array('giftcard_key' => $giftId));

						} elseif ($totamo > $giftAmount) {
							$giftcard_discount_amt = $giftAmount;
							$this->Giftcards->updateAll(array('avail_amount' => 0), array('giftcard_key' => $giftId));
							$orderitem_data->discountAmount = $giftAmount;
							$orderitem_data->giftamount = $giftAmount;

						} else {
							$giftcard_discount_amt = $giftAmount;
							$this->Giftcards->updateAll(array('avail_amount' => 0), array('giftcard_key' => $giftId));
							$orderitem_data->discountAmount = $giftAmount;
							$orderitem_data->giftamount = 0;
						}
					}

					//echo $userEnterCreditAmt.'cr amount'; die;
					if ($userEnterCreditAmt != 0 || $userEnterCreditAmt != '') {
						$orderitem_data->discountType = 'Credit';
						$orderitem_data->discountAmount = $userEnterCreditAmt;
					}

					if ($_POST['coupon_code'] != "") {
						
						$coupon_id = $_POST['coupon_code'];
						$getcouponvaluetwo = $this->Sellercoupons->find()->where(['couponcode' => $coupon_id])->first();
						$couponCodeId = $getcouponvaluetwo['id'];
						$coupontype = $getcouponvaluetwo['type'];
						$sourceid = $getcouponvaluetwo['sourceid'];
						$sellerid = $getcouponvaluetwo['sellerid'];
						$discount_amountTwo = $getcouponvaluetwo['couponpercentage'];
						$discount_amountTwo = ($discount_amountTwo / 100);
						if (!empty($getcouponvaluetwo)) {
							$iteid = $itemUser['items'][$j]['itemid'];
							$itemdata = $this->Items->find()->where(['id' => $iteid])->first();
							$cateid = $itemdata['category_id'];
							if ($coupontype == "item" || $coupontype == "facebook") {
								if ($sourceid == $iteid) {
									//$commiItemTotalPrice = floatval($itemUser['items'][$j]['unit_price'] * ($discount_amountTwo));
									$commiItemTotalPrice = floatval($item_price * ($discount_amountTwo));
									
									$commissionCost = round($commiItemTotalPrice, 2);
									$orderitem_data->discountType = 'Coupon Discount';
									$orderitem_data->discountAmount = $commissionCost;
									$commiItemTotalPrice = 0;
								} else {
									$orderitem_data->discountType = 0;
									$orderitem_data->discountAmount = 0;
									$commissionCost = 0;
								}
							} else if ($coupontype == "category") {
								if ($sellerid == $itemUser['userid']) {
									if ($sourceid == $cateid) {
										//$commiItemTotalPrice = $itemUser['items'][$j]['unit_price'] * $discount_amountTwo;
										$commiItemTotalPrice = floatval($item_price * ($discount_amountTwo));
										$commissionCost = round($commiItemTotalPrice, 2);
										$orderitem_data->discountType = 'Coupon Discount';
										$orderitem_data->discountAmount = $commiItemTotalPrice;
									} else {
										$orderitem_data->discountType = 0;
										$orderitem_data->discountAmount = 0;
										$commissionCost = 0;
									}
								} else {
									$orderitem_data->discountType = 0;
									$orderitem_data->discountAmount = 0;
									$commissionCost = 0;
								}
							} else if ($coupontype == "cart") {
								if ($sellerid == $itemUser['userid']) {
									//$commiItemTotalPrice = floatval(($itemUser['items'][$j]['unit_price']) * ($discount_amountTwo));
									$commiItemTotalPrice = floatval($item_price * ($discount_amountTwo));
									$commissionCost = round($commiItemTotalPrice, 2);
									$orderitem_data->discountType = 'Coupon Discount';
									$orderitem_data->discountAmount = $commissionCost;
									$commiItemTotalPrice = 0;
								} else {
									$orderitem_data->discountType = 0;
									$orderitem_data->discountAmount = 0;
									$commissionCost = 0;
								}
							}
							$rangeval = $getcouponvaluetwo['remainrange'];
							$rangevals = $rangeval > 1 ? $rangeval - 1 : 0;
							$this->Sellercoupons->updateAll(array('remainrange' => $rangevals), array('id' => $getcouponvaluetwo['id']));
						}
					}


					$orderitem_dataresult = $this->Order_items->save($orderitem_data);

					//echo '<pre>'; print_r($orderitem_dataresult); die;

					if ($itemId == 0) {
						$this->Carts->updateAll(array('payment_status' => 'success'), array('user_id' => $userId, 'item_id' => $itemUser['items'][$j]['itemid']));
					}
					$itemModel = $this->Items->find()->where(['id' => $itemUser['items'][$j]['itemid']])->first();
					$quantityItem = $itemModel['quantity'];
					$user_id = $itemModel['user_id'];
					$itemname[] = $itemModel['item_title'];
					$itemmailids[] = $itemModel['id'];
					$custmrsizeopt[] = $cartSize;
					$sellersizeopt[] = $cartSize;
					$selleritemmailids[] = $itemModel['id'];
					$selleritemname[] = $itemModel['item_title'];
					$itemopt = $itemModel['size_options'];
					$totquantity[] = $itemUser['items'][$j]['quantity'];
					$sellertotquantity[] = $itemUser['items'][$j]['quantity'];

					if (!empty($itemopt)) {
						if ($itemUser['items'][$j]['size'] != "") {
							$seltsize = $itemUser['items'][$j]['size'];
							$sizeqty = $itemopt;
							$sizeQty = json_decode($sizeqty, true);
							$balance_quantity = $sizeQty['unit'][$seltsize] - $itemUser['items'][$j]['quantity'];
							$balance_quantity = $balance_quantity < 1 ? 0 : $balance_quantity;
							$sizeQty['unit'][$seltsize] = $balance_quantity;
						}
					}
					
					/* update total quantity */
					$balance_quantity = $quantityItem - $itemUser['items'][$j]['quantity'];
					$balance_quantity = $balance_quantity < 1 ? 0 : $balance_quantity;
					$this->Items->updateAll(array('quantity' => $balance_quantity), array('id' => $itemUser['items'][$j]['itemid']));
					

					if (!empty($itemopt)) {
						$this->Items->updateAll(array('size_options' => json_encode($sizeQty)), array('id' => $itemUser['items'][$j]['itemid']));
					}

					$itemComission = 0;
					$commiItemTotalPrice = 0;
					$this->loadModel('Commissions');
					$commiDetails = $this->Commissions->find()->where(['active' => '1'])->all();
					$commissionItemConvert = $this->Currency->conversion($cur, $defaultcurrency_data['price'], $itemTotalPrice);
					foreach ($commiDetails as $commi) {
						$min_val = $commi['min_value'];
						$max_val = $commi['max_value'];
						if ($commissionItemConvert >= $min_val && $commissionItemConvert <= $max_val) {
							if ($commi['type'] == '%') {
								$amount = (floatval($commissionItemConvert) / 100) * ($commi['amount']);
								$commiItemTotalPrice += $amount;
							}
						}
					}
					if (count($commiDetails) < 0) {
						$commission_amount = (floatval($itemTotalPrice) / 100) * $Sitesettings['credit_percentage'];
						$commiItemTotalPrice = $commission_amount;
					}
					$itemComission = $this->Currency->conversion($defaultcurrency_data['price'], $cur, $commiItemTotalPrice);
					$orderComission += $itemComission;

				}
				//$totalcost = $_POST['grand_total']-$orderComission;
				$this->Orders->updateAll(array('discount_amount'=>$giftcard_discount_amt,'admin_commission' => $orderComission, 'coupon_id' => $couponCodeId), array('orderid' => $orderId));
				$invoiceId = $this->Invoices->find()->order(['invoiceid' => 'DESC'])->toArray();
				$invoiceId = $invoiceId[0]['invoiceid'] + 1;
				$invoice_data = $this->Invoices->newEntity();
				$invoice_data->invoiceno = 'INV' . $invoiceId . $userId;
				$invoice_data->invoicedate = time();
				$invoice_data->invoicestatus = 'Completed';
				if ($paymentType == "braintree")
					$invoice_data->paymentmethod = 'Braintree';
				else
					$invoice_data->paymentmethod = 'COD';
				$invoicedata_result = $this->Invoices->save($invoice_data);
				$invoiceId = $invoicedata_result->invoiceid;
				$invoiceorder_data = $this->Invoiceorders->newEntity();
				$invoiceorder_data->invoiceid = $invoiceId;
				$invoiceorder_data->orderid = $orderId;
				$this->Invoiceorders->save($invoiceorder_data);
			}


			/* * Update the Affiliate Product Share commission save to Sharing person * */
            
            $shareproducts = TableRegistry::get('Shareproducts')->find()->where(['receiver_id' => $userId])->where(['status' => 'visit'])->all();
            if(!empty($shareproducts)){
                foreach($shareproducts as $sharepdt) {
                    $sharepdtid = $sharepdt['item_id'];
                    if(in_array($sharepdtid, $itemmailids)) {

                 $itemModel = TableRegistry::get('Items')->find()->where(['id' => $sharepdtid])->first();
                 
                 $orderitemModel = TableRegistry::get('Order_items')->find('all')->where(['orderid' => $orderId])->first();
                 // $ordersize = $orderitemModel->item_size;
                 //    if ($ordersize != "" && $ordersize != 0) {
                 //         $product_store = json_decode($itemModel['size_options'], true);

                         
                 //     if(in_array($ordersize,$product_store['size']))
                 //     {
                 //          $itemprice = $product_store['price'][$ordersize];
                           
                 //     }
                 //    } else {
                 //        $itemprice = $itemModel->price;
                 //    }

                    $itemprice = $orderitemModel->itemprice; //quantity based price
                    $affiliatecommission = $itemModel->affiliate_commission;
                    $commission_amount =  $itemprice * $affiliatecommission / 100;

                    $sharedquery = TableRegistry::get('Shareproducts')->query();
                    $shareduserquery = TableRegistry::get('Users')->query();
                    $sharedquery->update()->set(['order_id' => $orderId, 'status' => 'purchased', 'share_amount' => $commission_amount])->where(['receiver_id' => $userId])->where(['item_id' => $sharepdtid])->where(['status' => 'visit'])->execute();

                    }
                }
            }
           
            /* * End Update the Affiliate Product Share commission save to Sharing person * */

			$referrer_id = $userModel['referrer_id'];
			$userid = $userModel['id'];
			if (!empty($referrer_id)) {
				$referrer_ids = json_decode($referrer_id);
				$sixtythdate = strtotime($userModel['created_at']) + 5184000;
				$createddate = strtotime($userModel['created_at']);
				if ($createddate < $sixtythdate && time() <= $sixtythdate && $referrer_ids->first == 'first') {
					$userinvites = TableRegistry::get('Userinvitecredits');
					$userinvite_data = $userinvites->newEntity();
					$userinvite_data->user_id = $userid;
					$userinvite_data->invited_friend = $referrer_ids->reffid;
					$userinvite_data->credit_amount = $creditAmtByAdmin;
					$userinvite_data->status = "Used";
					$userinvite_data->cdate = time();
					$userinvites->save($userinvite_data);
					$reff_id['reffid'] = $referrer_ids->reffid;
					$reff_id['first'] = 'Purchased';
					$json_ref_id = json_encode($reff_id);

					$referalquery = TableRegistry::get('Users')->query();
					$referquery = TableRegistry::get('Users')->query();
					$referalquery->update()->set(['referrer_id' => $json_ref_id])
					->where(['id' => $userid])->execute();
					$usercredit_amt = TableRegistry::get('Users')->find()->where(['id' => $referrer_ids->reffid])->first();
					$total_credited_amount = $usercredit_amt['credit_total'];
					$total_credited_amount = $total_credited_amount + $creditAmtByAdmin;
					TableRegistry::get('Users')->query()->update()->set(['credit_total' => $total_credited_amount])
					->where(['id' => $referrer_ids->reffid])->execute();

				}
				/* Push Notifications & Logs */
				$userdevicestable = TableRegistry::get('Userdevices');
				$userddett = $userdevicestable->find('all')->where(['user_id' => $referrer_ids->reffid])->all();
				foreach ($userddett as $userd) {
					$deviceTToken = $userd['deviceToken'];
					$badge = $userd['badge'];
					$badge += 1;
					$querys = $userdevicestable->query();
					$querys->update()
					->set(['badge' => $badge])
					->where(['deviceToken' => $deviceTToken])
					->execute();

					$user_datas = TableRegistry::get('Users')->find()->where(['id' => $userid])->first();
					$user_profile_image = $user_datas['profile_image'];
					if ($user_profile_image == "")
						$user_profile_image = "usrimg.jpg";

					if (isset($deviceTToken)) {
						$pushMessage['type'] = 'credit';
						$pushMessage['user_id'] = $userid;
						$pushMessage['user_name'] = $user_datas['username'];
						$pushMessage['user_image'] = $user_profile_image;
						$user_detail = TableRegistry::get('Users')->find()->where(['id' => $referrer_ids->reffid])->first();
						I18n::locale($user_detail['languagecode']);
						$pushMessage['message'] = __d('user', "You have received a") . " " . $creditAmtByAdmin . " " . __d('user', 'credit regarding your friends first purchase');
						$messages = json_encode($pushMessage);
						$this->pushnot($deviceTToken, $messages, $badge);
					}
				}

			}

			if (!empty($referrer_id)) {
				if (trim($user_datas['profile_image']) == "")
					$userImg = "usrimg.jpg";
				else
					$userImg = $user_datas['profile_image'];
				$image['user']['image'] = $userImg;
				$image['user']['link'] = SITE_URL . "people/" . $user_datas['username_url'];
				$loguserimage = json_encode($image);
				$loguserlink = "";
				$notifymsg = $loguserlink . " -___- Your account has credited for referral bonus";
				$messages = "Your account has credited for referral bonus with " . $_SESSION['default_currency_symbol'] . $creditAmtByAdmin;
				$logdetails = $this->addlog('credit', $userid, $referrer_ids->reffid, 0, $notifymsg, $messages, $loguserimage);
			}

			echo '{"status":"true","message":"Ordered Successfully"}';
			die;

		} else {
			echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
			die;
		}
	}
	function forgotPassword()
	{
		$email = $_POST['email'];
		$this->loadModel('Users');
		$this->loadModel('Shops');
		if (!empty($email)) {
			$usr_datas = $this->Users->find()->where(['email' => $email])->first();//ByEmail($email);
			if (count($usr_datas) > 0) {
				$ismerchant = $this->Users->find()->where(['user_level' => 'shop'])->andWhere(['email' => $email])->count();
				if ($ismerchant > 0) {
					echo '{"status":"false","message":"Enter the correct user Email id"}';
					die;
				}
				$name = $usr_datas['first_name'];
				$reg_email = $usr_datas['email'];
				$use_id = $usr_datas['id'];
				if ($this->disableusercheck($use_id) == 0) {
					echo '{"status":"error","message":"The user has been blocked by admin"}';
					die;
				}
				if (!empty($reg_email)) {
					$usr_datas->id = $use_id;
					$this->Users->save($usr_datas);
					$userstable = TableRegistry::get('Users');
					$sellerdetails = $userstable->find()->where(['id' => $use_id])->first();
					$setngs = TableRegistry::get('sitesettings')->find('all')->first();
					$email = $sellerdetails['email'];
					$aSubject = $setngs['site_name'] . ' - Your new password has arrived';
					$aBody = '';
					$template = 'passwordreset';
					$emailid = base64_encode($reg_email);
					$time = time();
					$setdata = array('name' => $sellerdetails['first_name'], 'access_url' => SITE_URL . "setpassword/" . $emailid . "~" . $time);
					$this->sendmail($email, $aSubject, $aBody, $template, $setdata);
					echo '{"status":"true","message":"Check your email in a few minutes for instructions to reset your password"}';
					die;

				}
			} else {
				echo '{"status":"false","message":"User not found"}';
				die;
			}
		}
	}

	public function addproductfaq()
	{
		$user_id = $_POST['user_id'];
		$item_id = $_POST['item_id'];
		$content = $_POST['content'];
		$type = $_POST['type'];

		$this->loadModel('Items');

		if(!isset($user_id) || !isset($item_id) || !isset($content) 
			|| !isset($type)
			)
		{
			echo '{"status":"false","message":"Validation"}';
			die;
		}

		$getItem = $this->Items->find()->where(['id' => $item_id])->first();

		$productfaqTable = TableRegistry::get('Productfaq');
        $productfaq = $productfaqTable->newEntity();
        $productfaq->item_id = $item_id;
        $productfaq->user_id = $user_id;
        $productfaq->seller_id = $getItem->user_id;
        $productfaq->content = $content;
        $productfaq->type = $type;
        if($type == 'answer' && isset($_POST['parent_id']))
        {
        	$productfaq->parent_id = $_POST['parent_id'];
        }
        if ($productfaqTable->save($productfaq)) {
        	echo '{"status":"true","message":"'.ucfirst($type).' added successfully"}';
			die;
        }

	}

	function group_by($key, $data) {
	    $result = array();

	    //echo '<pre>'; print_r($data); die;

	    foreach($data as $val) {
	        if(array_key_exists($key, $val)){
	            $result[$val[$key]][] = $val;
	        }else{
	            $result[""][] = $val;
	        }
	    }
	    krsort($result);
	    //echo '<pre>'; print_r($result); die;
	    return $result;
	}

	public function productfaqlist()
	{
		if(!isset($_POST['item_id']) || !isset($_POST['offset']) || !isset($_POST['limit']))
		{
			echo '{"status":"false","message":"Validation"}';
			die; 
		}
		$dataSourceObject = ConnectionManager::get('default');
    	$stmt = $dataSourceObject->execute("SELECT  t1.id AS question_id, 
                                                t1.parent_id AS question_parent_id, 
                                                t1.user_id AS question_user_id, 
                                                t1.item_id AS question_item_id,
                                                t1.content AS question_content, 
                                                t2.id AS answer_id, 
                                                t2.parent_id AS answer_parentid, 
                                                t2.user_id AS answer_user_id, 
                                                t2.item_id AS answer_item_id,
                                                t2.content as answer_content
				FROM fc_productfaq AS t1
				LEFT JOIN fc_productfaq AS t2 ON t2.parent_id = t1.id
				LEFT JOIN fc_productfaq AS t3 ON t3.parent_id = t2.id
				LEFT JOIN fc_productfaq AS t4 ON t4.parent_id = t3.id
				WHERE t1.item_id = ".$_POST['item_id']." AND t1.parent_id='0' ORDER BY t1.id, t2.id DESC")->fetchAll('assoc');

    	if(empty($stmt))
    	{
    		echo '{"status":"false","message":"Data not found"}';
			die; 
    	}

	     $byGroup = $this->group_by("question_id", $stmt);

	     $results = array();
	     $q=0;
	    foreach($byGroup as $ssval)
	    {

	        $byssssks = $this->group_by('answer_parentid',$ssval);
	        $a = 0;


	        foreach($byssssks as $key=>$ksk)
	        {

	            foreach($ksk as $ksey=>$dfsdfsdfs)
	            {

	                $userstable = TableRegistry::get('Users');
	                $q_user = $userstable->find()->where(['id' => $dfsdfsdfs['question_user_id']])->first();

	                $results[$q]['id'] = $dfsdfsdfs['question_id'];
	                $results[$q]['question'] = $dfsdfsdfs['question_content'];
	                $results[$q]['user_id'] = $dfsdfsdfs['question_user_id'];
	                $results[$q]['username'] = $q_user['username'];
	                $results[$q]['profile_image'] = $q_user['profile_image'];
	                

	                if($dfsdfsdfs['answer_content'] != '' && $a < 1 )
	                {
	                    $a_user = $userstable->find()->where(['id' => $dfsdfsdfs['answer_user_id']])->first();
	                    $results[$q]['answer_count'] = count($ksk);

	                    $results[$q]['answer'][$a]['user_id'] = $dfsdfsdfs['answer_user_id'];
	                    $results[$q]['answer'][$a]['answer_id'] = $dfsdfsdfs['answer_id'];
	                    $results[$q]['answer'][$a]['username'] = $a_user['username'];
	                    $results[$q]['answer'][$a]['profile_image'] = $a_user['profile_image'];
	                    $results[$q]['answer'][$a]['parent_id'] = $dfsdfsdfs['answer_parentid'];
	                    $results[$q]['answer'][$a]['answer'] = $dfsdfsdfs['answer_content'];
	                }elseif($dfsdfsdfs['answer_content'] == ''){
	                	$results[$q]['answer_count'] = 0;
	                }
	                $a++;
	            }
	            
	            
	        }
	        $q++;
	    }

	    $limitdata = array_slice(array_values($results), $_POST['offset'],$_POST['limit']); 
    	echo '{"status":"true","result":'.json_encode($limitdata).'}';
		die; 
	}


	public function getlatestproduct_faq($value='')
	{
		$dataSourceObject = ConnectionManager::get('default');
    	$stmt = $dataSourceObject->execute("SELECT  t1.id AS question_id, 
                                                t1.parent_id AS question_parent_id, 
                                                t1.user_id AS question_user_id, 
                                                t1.item_id AS question_item_id,
                                                t1.content AS question_content, 
                                                t2.id AS answer_id, 
                                                t2.parent_id AS answer_parentid, 
                                                t2.user_id AS answer_user_id, 
                                                t2.item_id AS answer_item_id,
                                                t2.content as answer_content
				FROM fc_productfaq AS t1
				LEFT JOIN fc_productfaq AS t2 ON t2.parent_id = t1.id
				LEFT JOIN fc_productfaq AS t3 ON t3.parent_id = t2.id
				LEFT JOIN fc_productfaq AS t4 ON t4.parent_id = t3.id
				WHERE t1.item_id = ".$value['item_id']." AND t1.parent_id='0' ORDER BY t1.id, t2.id DESC")->fetchAll('assoc');

    	if(empty($stmt))
    	{
    		return array();
			//die; 
    	}

    	$setngs = $this->Sitesettings->find()->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}

	     $byGroup = $this->group_by("question_id", $stmt);

	     $results = array();
	     $q=0;
	    foreach($byGroup as $ssval)
	    {

	        $byssssks = $this->group_by('answer_parentid',$ssval);
	        $a = 0;
	        foreach($byssssks as $key=>$ksk)
	        {
	            foreach($ksk as $ksey=>$dfsdfsdfs)
	            {
	                $userstable = TableRegistry::get('Users');
	                $q_user = $userstable->find()->where(['id' => $dfsdfsdfs['question_user_id']])->first();

	                //print_r($ksk); die;

	                $results[$q]['id'] = $dfsdfsdfs['question_id'];
	                $results[$q]['question'] = $dfsdfsdfs['question_content'];
	                $results[$q]['user_id'] = $dfsdfsdfs['question_user_id'];
	                $results[$q]['username'] = $q_user['username'];
	                $results[$q]['profile_image'] = (!isset($q_user['profile_image'])) ? $img_path . 'media/avatars/thumb150/' . $q_user['profile_image'] : $img_path . 'media/avatars/thumb150/usrimg.jpg';
	                

	                if($dfsdfsdfs['answer_content'] != '' && $a < 1 )
	                {
	                	$results[$q]['answer_count'] = count($ksk);
	                    $a_user = $userstable->find()->where(['id' => $dfsdfsdfs['answer_user_id']])->first();
	                    $results[$q]['answer'][$a]['user_id'] = $dfsdfsdfs['answer_user_id'];
	                    $results[$q]['answer'][$a]['answer_id'] = $dfsdfsdfs['answer_id'];
	                    $results[$q]['answer'][$a]['username'] = $a_user['username'];
	                    $results[$q]['answer'][$a]['profile_image'] = (!isset($a_user['profile_image'])) ? $img_path . 'media/avatars/thumb150/' . $a_user['profile_image'] : $img_path . 'media/avatars/thumb150/usrimg.jpg';
	                    $results[$q]['answer'][$a]['parent_id'] = $dfsdfsdfs['answer_parentid'];
	                    $results[$q]['answer'][$a]['answer'] = $dfsdfsdfs['answer_content'];
	                }elseif($dfsdfsdfs['answer_content'] == ''){
	                	$results[$q]['answer_count'] = 0;
	                }
	                $a++;
	            }
	            
	            
	        }
	        $q++;
	    }

	    return $limitdata = array_slice(array_values($results), 0,2); 
	}

	public function readotheranswers()
	{
		if(!isset($_POST['parent_id']) || !isset($_POST['offset']) || !isset($_POST['limit']))
		{
			echo '{"status":"false","message":"Validation"}';
			die;
		}
		$this->loadModel('Productfaq');
		$this->loadModel('Users');
		$this->loadModel('Shops');
		$answers = $this->Productfaq->find('all', array(
				'conditions' => array(
					'parent_id' => $_POST['parent_id']
				),
				'limit' => $_POST['limit'],
				'offset' => $_POST['offset'],
				'order' => 'id DESC',
			))->all();

		$setngs = $this->Sitesettings->find()->toArray();
		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}

		$resultArray = array();
		foreach($answers as $key=>$eachanswer)
		{
			$getUser = $this->Users->find('all', array(
				'conditions' => array(
					'id' => $eachanswer->user_id
				)
				))->first();

			if($getUser->user_level == 'shop')
	        {
	            $getshopContent = $this->Shops->find()->where(['user_id'=>$getUser->id])->first();	
	            $resultArray[$key]['store_id'] = $getshopContent->id;
	            $resultArray[$key]['shop_name'] = $getshopContent->shop_name;
	        }

			$resultArray[$key]['id'] = $eachanswer->id;
			$resultArray[$key]['user_id'] = $eachanswer->user_id;
			$resultArray[$key]['user_name'] = $getUser->username;
			$resultArray[$key]['user_image'] = (!isset($getUser->profile_image)) ? $img_path . 'media/avatars/thumb150/usrimg.jpg' : $img_path . 'media/avatars/thumb150/' . $getUser->profile_image ;
			$resultArray[$key]['answer'] = $eachanswer->content;
		}
		echo '{"status":"true","result":'.json_encode($resultArray).'}';
		die; 
	}

	function sitemaintenance()
	{
		$this->loadModel('Managemodules');
		$this->loadModel('Users');

		$managemoduleModel = $this->Managemodules->find()->first();
		if ($managemoduleModel->site_maintenance_mode == 'yes') {
			return 0;
		} else {
			return 1;
		}
	}
	function disableusercheck($userId)
	{

		$this->loadModel('Users');
		$user = $this->Users->find()->where(['id' => $userId])->andWhere(['user_status' => 'enable'])->first();

		return count($user);
	}

	function contactAdmin()
	{

		$this->loadModel('Users');
		$this->loadModel('Helps');
		$this->loadModel('Sitesettings');
		$contact = $this->Helps->find('all');
		foreach ($contact as $contacts) {
			$address = $contacts['contact'];
		}

		$contactaddress = json_decode($address, true);
		if (!empty($this->request->data)) {
			$full_name = $_POST['full_name'];
			$user_name = $_POST['user_name'];
			$user_id = $_POST['user_id'];
			$emailId = $_POST['email'];
			$topic = $_POST['topic'];
			$order_no = $_POST['order_no'];
			$message = $_POST['message'];

			$setngs = $this->Sitesettings->find()->where(['id' => 1])->first();
			if ($setngs['gmail_smtp'] == 'enable') {
				$this->Email->smtpOptions = array(
					'port' => $setngs['smtp_port'],
					'timeout' => '30',
					'host' => 'ssl://smtp.gmail.com',
					'username' => $setngs['noreply_email'],
					'password' => $setngs['noreply_password']
				);

				$this->Email->delivery = 'smtp';
			}
			$this->Email->to = $contactaddress['emailid'];
			$this->Email->subject = __d('user', "Enquiry from a user");
			$this->Email->from = SITE_NAME . "<" . $setngs['noreply_email'] . ">";
			$this->Email->sendAs = "html";
			$this->Email->template = 'contact_mails';
			$this->set('name', $full_name);
			$this->set('userAccount', $user_name);
			$this->set('topic', $topic);
			$this->set('order', $order_no);
			$this->set('message', $message);
			$this->set('email', $email);

			$sitesettings = TableRegistry::get('sitesettings');
			$setngs = $sitesettings->find()->first();

			$email = $contactaddress['emailid'];
			$aSubject = __d('user', "Enquiry from a user");
			$aBody = 'test';
			$template = 'contact_mails';
			$setdata = array('name' => $full_name, 'userAccount' => $user_name, 'topic' => $topic, 'order' => $order_no, 'message' => $message, 'email' => $emailId, 'setngs' => $setngs);
			if ($this->sendmail($email, $aSubject, $aBody, $template, $setdata)) {
				echo '{"status":"true","message":"Email sent successfully"}';
				die;
			} else {
				echo '{"status":"false","message":"Something went to be wrong,Please try again later."}';
				die;
			}
		}
	}

	function upload($imageurl, $fileName = null, $type = "user")
	{
		global $setngs;
		global $loguser;
		$userId = $loguser[0]['User']['id'];
		$mediaUserName = $setngs[0]['Sitesetting']['media_server_username'];
		$hostName = $setngs[0]['Sitesetting']['media_server_hostname'];
		$media_url = $setngs[0]['Sitesetting']['media_url'];
		$password = $setngs[0]['Sitesetting']['media_server_password'];
		$site_url = SITE_URL;
		if ($media_url == '') {
			$media_url = $site_url;
		}

		if ($type == "item") {
			$user_image_path = "media/items/";
		} else {
			$user_image_path = "media/avatars/";
		}

		$newimage = "";
		$thumbimage = "";
		$newname = time() . '_' . $userId . ".jpg";
		if ($fileName != null) {
			$newname = $fileName;
		}
		$finalPath = $user_image_path . "original/";
		$thumbimage1 = $user_image_path . "thumb350/";
		$thumbimage2 = $user_image_path . "thumb150/";
		$out = 0;
		while ($out == 0) {
			$i = file_get_contents($imageurl);
			if ($i != false) {
				$out = 1;
			}
		}
		$fori = fopen($finalPath . $newname, 'wb');
		$fori1 = fopen($thumbimage1 . $newname, 'wb');
		$fori2 = fopen($thumbimage1 . $newname, 'wb');
		fwrite($fori, $i);
		fwrite($fori1, $i);
		fwrite($fori2, $i);
		fclose($fori);
		chmod($finalPath . $newname, 0666);
		return $newname;

	}

	/*
		Fantacy v5
	*/
	public function addreview()
	{
		$user_id = $this->request->data('user_id');
		$item_id = $this->request->data('item_id');
		$order_id = $this->request->data('order_id');
		$rating = $this->request->data('rating');
		$review = $this->request->data('review');

		
	}

	public function suggestedItems($userid=null)
	{
			$searchitemstable = TableRegistry::get('Searchitems');
            $itemstable = TableRegistry::get('Items');
            $this->loadModel('Searchitems');

            $datanewSourceObject = ConnectionManager::get('default');
            $result = array();

            if($userid != ''){
            	$getSearchlist = $datanewSourceObject->execute("SELECT * from fc_searchitems where userid=".$userid." ORDER BY `id` DESC
            ")->fetchAll('assoc');

            	foreach ($getSearchlist as $element) {
	                $result[$element['category_id']][] = $element;
	            }
            }else{
            	$result = array();
            }
            
            $results = array();
            
            $key=0;
            foreach($result as $eachItem)
            {
            	//if($key > 2)
            	//	continue;

                $getitems = $itemstable->find()->contain('Photos')->contain('Forexrates')->contain('Users')->contain('Shops')->where(['Items.status'=>'publish','Items.id'=>$eachItem[0]['sourceid']])->first();
                $related_items = $itemstable->find()->contain('Photos')->contain('Forexrates')->contain('Users')->contain('Shops')->where(['Items.status'=>'publish','Items.id !='=>$getitems->id,'Items.category_id'=>$eachItem[0]['category_id']])->offset(0)->limit(8)->toArray();

                if(!empty($getitems) && !empty($related_items))
                {
                	$firstItem = json_decode(json_encode($getitems), true);
	                $pendingItems = json_decode(json_encode($related_items), true);
	                $mergeArray = array_merge(array($firstItem),$pendingItems);
					$results[$key]['related_products'] = $mergeArray;	
					$key++;
                }
            }

            $favitems_ids = array();
            $resultArray = $this->convertJsonHome($results, $favitems_ids, $userid, '', 'suggest');

			

            return $resultArray;
	}

	public function convertJsonHomesuggestnew($items_data, $favitems_ids = null, $user_id = null, $temp = null)
	{

		$this->loadModel('Contactsellers');
		$this->loadModel('Itemfavs');
		$this->loadModel('Sitequeries');
		$this->loadModel('Facebookcoupons');
		$this->loadModel('Forexrates');
		$this->loadModel('Photos');
		$this->loadModel('Users');
		$this->loadModel('Sitesettings');
		$setngs = $this->Sitesettings->find()->toArray();
		$photos = $this->Photos->find()->order(['id DESC'])->all();

		$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();

		$resultArray = array();
		$resultArray['type'] = "Everything";
		if ($type != null)
			$resultArray['type'] = $type;
		$resultArray = array();
		$shareCouponDetail = array();

		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}
		$userDetail = $this->Users->find()->where(['id' => $user_id])->first();

		if (!empty($userDetail))
			$userId = $userDetail['id'];
		else
			$userId = $userId;

		$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();
		if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "" || count($userDetail) == 0) {
			$cur_symbol = $forexrateModel['currency_symbol'];
			$cur = $forexrateModel['price'];
		} else {
			$cur_symbol = $currency_value['currency_symbol'];
			$cur = $currency_value['price'];
		}
		$items_fav_data = $this->Itemfavs->find()->where(['user_id' => $user_id])->all();//
		if (count($items_fav_data) > 0) {
			foreach ($items_fav_data as $favitems) {
				$favitems_ids[] = $favitems['item_id'];
			}
		} else {
			$favitems_ids = array();
		}

		$resultArray = array();
		foreach ($items_data as $key => $listitem) {
			//print_r($listitem); die;
				$resultArray[$key]['related_items'] = $this->convertJsonHomesuggested($listitem, $favitems_ids, $user_id);
			}

		return array_values($resultArray);
	}

	function suggestitem_viewmore($userid=null, $offset=null, $limit=null)
    {
    	//echo $userid; die;
        $this->loadModel('Items');
        $this->loadModel('Searchitems');
        $itemsTable = TableRegistry::get('Items');
        $searchitemstable = TableRegistry::get('Searchitems');
        
        $suggestItemModel = $this->Searchitems->find()->where(['userid' => $userid])->offset($offset)->limit($limit)->all();

        $s = 0;
        $items_data = array();
        foreach($suggestItemModel as $key=>$val)
        {
            $getitems = $itemsTable->find()->contain('Photos')->contain('Forexrates')->contain('Users')->contain('Shops')->where(['Items.status'=>'publish','Items.id'=>$val->sourceid])->first();
            $items_data[$s] = $getitems; 
            $s++;   
        }

        $resultArray = $this->convertJsonHome($items_data, $favitems_ids, $userid);
        return $resultArray;
    }

    public function testfunction()
    {
    	$itemreviewTable = TableRegistry::get('Itemreviews');
    	$getreviews = $itemreviewTable->find()->Contain('users')->all();
    	echo '<pre>'; print_r($getreviews); die;
    }

    function affiliateDetails()
	{
		$item_id = $_POST['item_id'];
		$user_id = $_POST['user_id'];
		$this->loadModel('Contactsellers');
		$this->loadModel('Itemreviews');
		$this->loadModel('Sitequeries');
		$this->loadModel('Facebookcoupons');
		$this->loadModel('Forexrates');
		$this->loadModel('Sellercoupons');
		$this->loadModel('Photos');
		$this->loadModel('Users');
		$this->loadModel('Searchitems');
		$this->loadModel('Followers');
		$this->loadModel('Items');
		$this->loadModel('Itemfavs');
		$this->loadModel('Storefollowers');
		$this->loadModel('Shops');
		$this->loadModel('Fashionusers');
		$this->loadModel('Sitesettings');
		$this->loadModel('Comments');
		$setngs = $this->Sitesettings->find()->toArray();

		$forexrateModel = $this->Forexrates->find()->where(['cstatus' => 'default'])->first();

		//if(isset($_POST['get_type']) && $_POST['get_type'] == 'search' && !empty($user_id))
		

		$resultArray = array();

		if (SITE_URL == $setngs[0]['media_url']) {
			$img_path = $setngs[0]['media_url'];
		} else {
			$img_path = $setngs[0]['media_url'];
		}
		$userDetail = $this->Users->find()->where(['id' => $user_id])->first();

		if (!empty($userDetail))
			$userId = $userDetail['id'];
		else
			$userId = $userId;

		$currency_value = $this->Forexrates->find()->where(['id' => $userDetail['currencyid']])->first();
		if ($currency_value['currency_code'] == $forexrateModel['currency_code'] || $currency_value['currency_code'] == "") {

			$cur_symbol = $forexrateModel['currency_symbol'];

			$cur = $forexrateModel['price'];
		} else {

			$cur_symbol = $currency_value['currency_symbol'];
			$cur = $currency_value['price'];
		}

		$listitem = TableRegistry::get('Items')->find()->contain('Forexrates')->where(['Items.id' => $item_id])->andWhere(['Items.status' => 'publish'])->andWhere(['Items.affiliate_commission <>' => 0])->first();

		if (!isset($listitem) && empty($listitem)) {
			echo '{"status": "false", "message": "No data found"}';
			die;
		}


		if(!empty($user_id))
		{
			//echo $listitem['category_id']; die;
			 $this->Searchitems->deleteAll(array('sourceid' => $item_id, 'userid' => $user_id), false);

			$item_categoryid = $listitem['category_id'];
	        $searchitemstable = TableRegistry::get('Searchitems');
	        $searchitems = $searchitemstable->newEntity();
	        $searchitems->sourceid = $item_id;
	        $searchitems->category_id = $item_categoryid;
	        $searchitems->userid = $user_id;
	        $searchitems->type = 'item';
	        $result = $this->Searchitems->save($searchitems);
	        $itemId = $result->id;
		}
		
		$price = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $listitem['price']);

		//echo count($listitem); die;

		

		$reportUsers = '';
		$process_time = $listitem['processing_time'];
		if ($process_time == '1d') {
			$process_time = "One business day";
		} elseif ($process_time == '2d') {
			$process_time = "Two business days";
		} elseif ($process_time == '3d') {
			$process_time = "Three business days";
		} elseif ($process_time == '4d') {
			$process_time = "Four business days";
		} elseif ($process_time == '2ww') {
			$process_time = "One-Two weeks";
		} elseif ($process_time == '3w') {
			$process_time = "Two-Three weeks";
		} elseif ($process_time == '4w') {
			$process_time = "Three-Four weeks";
		} elseif ($process_time == '6w') {
			$process_time = "Four-Six weeks";
		} elseif ($process_time == '8w') {
			$process_time = "Six-Eight weeks";
		}
		$shareSeller = $listitem['share_coupon'];

		$shareCouponDetail = $this->Facebookcoupons->find()->where(['item_id' => $listitem['id']])->andWhere(['user_id' => $user_id])->all();
		if (count($shareCouponDetail) != 0)
			$shareUser = "yes";
		else
			$shareUser = "no";

		$convertPrice = round($listitem['price'] * $forexrateModel['price'], 2);
		if ($listitem['featured'] == 1)
			$convertdefaultprice = round(($listitem['price'] * 2) * $listitem['price'], 2);
		else
			$convertdefaultprice = round($listitem['price'] * $listitem['price'], 2);

		$resultArray['id'] = $listitem['id'];
		$resultArray['item_title'] = $listitem['item_title'];
		$itemid = base64_encode($listitem['id'] . "_" . rand(1, 9999));
		$itemshareid = base64_encode($listitem['id'] . "_" . rand(1, 9999) . "_". $user_id);
		$resultArray['product_url'] = SITE_URL . 'listing/' . $itemid;
		$resultArray['product_share_url'] = SITE_URL . 'listing/' . $itemshareid;
		$resultArray['item_description'] = $listitem['item_description'];
		$resultArray['shipping_time'] = $process_time;
		$resultArray['currency'] = $cur_symbol;
		$resultArray['mainprice'] = $listitem['price'];
		$resultArray['commision_percentage'] = $listitem['affiliate_commission'];

		$tdy = strtotime(date("Y-m-d"));

		if (strtotime($listitem['dealdate']) == $tdy && $listitem['discount_type'] == 'daily') {
			$resultArray['deal_enabled'] = 'yes';
			$resultArray['pro_discount'] = 'dailydeal';
			$resultArray['discount_percentage'] = $listitem['discount'];
		} elseif($listitem['discount_type'] == 'regular') {
			$resultArray['deal_enabled'] = 'yes';
			$resultArray['pro_discount'] = 'regulardeal';
			$resultArray['discount_percentage'] = $listitem['discount'];
		}else{
			$resultArray['deal_enabled'] = 'no';
			$resultArray['discount_percentage'] = 0;
		}

		$resultArray['fbshare_discount'] = $listitem['share_discountAmount'];
		$resultArray['valid_till'] = (!isset($listitem['dealdate'])) ? '' : strtotime($listitem['dealdate']) ;
		$resultArray['quantity'] = $listitem['quantity'];
		$resultArray['cod'] = $listitem['cod'];

		$itemDiscount = $this->Sellercoupons->find()->where([
			'sourceid' => $item_id,
			'type'=>'item',
			'remainrange !='=>'0'
			])->order(['id'=>'desc'])->first();


		$categoryDiscount = $this->Sellercoupons->find()->where([
			'sourceid' => $listitem['category_id'],
			'sellerid'=>$listitem['user_id'],
			'type'=>'category',
			'remainrange !='=>'0'
			])->order(['id'=>'desc'])->first();



		

		
		$now = strtotime(date('m/d/Y'));
		
		if($now <= strtotime($itemDiscount->validto))
		{
			$itemDiscount = $itemDiscount;
		}else{
			$itemDiscount = '';
		}


		if($now <= strtotime($categoryDiscount->validto))
		{
			$categoryDiscount = $categoryDiscount;
		}else{
			$categoryDiscount = '';
		}

		if((!empty($itemDiscount))){
			$resultArray['seller_offer']['couponcode'] = $itemDiscount->couponcode;
			$resultArray['seller_offer']['couponpercentage'] = $itemDiscount->couponpercentage;
			$resultArray['seller_offer']['validfrom'] = date("M d", strtotime($itemDiscount->validfrom));
			$resultArray['seller_offer']['validto'] = date("M d", strtotime($itemDiscount->validto));	
			$resultArray['seller_offer']['coupon_count'] = $itemDiscount->totalrange;
		}else{
			$resultArray['seller_offer'] = (object) array();
		}
		
		//xxx use this coupon to get extra 10% discount from April 10 to April 15. Limited for first 10 purchases only.
		if((!empty($categoryDiscount))){
			$resultArray['category_offer']['couponcode'] = $categoryDiscount->couponcode;
			$resultArray['category_offer']['couponpercentage'] = $categoryDiscount->couponpercentage;
			$resultArray['category_offer']['validfrom'] = date("M d", strtotime($categoryDiscount->validfrom));
			$resultArray['category_offer']['validto'] = date("M d", strtotime($categoryDiscount->validto));
			$resultArray['category_offer']['coupon_count'] = $categoryDiscount->totalrange;
		}else{
			$resultArray['category_offer'] = (object) array();
		}

		/*
		if((!empty($cartDiscount))){
			$resultArray['cart_offer']['couponcode'] = $cartDiscount->couponcode;
			$resultArray['cart_offer']['couponpercentage'] = $cartDiscount->couponpercentage;
			$resultArray['cart_offer']['validfrom'] = date("M d", strtotime($cartDiscount->validfrom));
			$resultArray['cart_offer']['validto'] = date("M d", strtotime($cartDiscount->validto));
			$resultArray['cart_offer']['coupon_count'] = $cartDiscount->totalrange;
		}else{
			$resultArray['cart_offer'] = (object) array();
		}
		*/

		//$resultArray['category_offer'] = (!empty($categoryDiscount)) ? $categoryDiscount->couponcode.' use this coupon to get extra '.$categoryDiscount->couponpercentage.'% discount from '.date("M d", strtotime($categoryDiscount->validfrom)).' to '.date("M d", strtotime($categoryDiscount->validto)).' limited for first '.$categoryDiscount->totalrange : '';

		//$resultArray['admin_offer'] = (object) array();

		$resultArray['size'] = [];

		if (empty($listitem['size_options'])) {
			$resultArray['size'] = [];
		} else {
			$sizes = json_decode($listitem['size_options'], true);
			$sqkey = 0;
			$setPrice = 0;
			foreach ($sizes['size'] as $val) {
				if (count($sizes['unit'][$val]) > 0) {
					$resultArray['size'][$sqkey]['name'] = $val;
					$resultArray['size'][$sqkey]['qty'] = $sizes['unit'][$val];
					$resultArray['size'][$sqkey]['price'] = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $sizes['price'][$val]);
					if(($sizes['unit'][$val] > 0) && ($setPrice==0))
					{
						$price = $this->Currency->conversion($listitem['forexrate']['price'], $cur, $sizes['price'][$val]);
						$setPrice++;
					}
					$sqkey++;
				}
			}
		}

		if ($currency_value['currency_code'] != $forexrateModel['currency_code'])
			$resultArray['price'] = $price;
		else
			$resultArray['price'] = $price;

		$shop_data = $this->Shops->find()->where(['id' => $listitem['shop_id']])->first();
		$shop_image = $shop_data['shop_image'];

		if ($shop_image == "")
			$shop_image = "usrimg.jpg";

		$resultArray['shop_id'] = $shop_data['id'];
		$resultArray['shop_name'] = $shop_data['shop_name_url'];
		$resultArray['shop_image'] = $img_path . 'media/avatars/thumb350/' . $shop_image;
		$resultArray['shop_address'] = $shop_data['shop_address'];

		$store_follow_status = $this->Storefollowers->find()->where(['store_id' => $shop_data['id']])->andwhere(['follow_user_id' => $user_id])->first();
		if (count($store_follow_status) == 0) {
			$resultArray['store_follow'] = "no";
		} else {
			$resultArray['store_follow'] = "yes";
		}

		if ($listitem['featured'] == 1)
			$convertdefaultprice = round(($listitem['price'] * 2) * $listitem['price'], 2);
		else
			$convertdefaultprice = round($listitem['price'] * $listitem['price'], 2);

		$resultArray['latitude'] = $shop_data['shop_latitude'];
		$resultArray['longitude'] = $shop_data['shop_longitude'];
		$likedcount = $this->Itemfavs->find()->where(['item_id' => $listitem['id']])->count();
		$resultArray['like_count'] = $likedcount;
		$resultArray['reward_points'] = floor($convertdefaultprice);
		$resultArray['share_seller'] = $shareSeller;
		$resultArray['share_user'] = $shareUser;

		if ($listitem['status'] == 'things') {
			$resultArray['buy_type'] = "affiliate";
		} else if ($listitem['status'] == 'publish') {
			$resultArray['buy_type'] = "buy";
		}
		$resultArray['affiliate_link'] = $listitem['bm_redircturl'];
		if ($listitem['status'] == 'publish') {
			$resultArray['approve'] = true;
		} else {
			$resultArray['approve'] = false;
		}

		$item_status = json_decode($listitem['report_flag'], true);

		if (in_array($user_id, $item_status)) {
			$report_status = "yes";
		} else {
			$report_status = "no";

		}
		$resultArray['report'] = $report_status;
		$liked_status = $this->Itemfavs->find()->where(['item_id' => $item_id])->andwhere(['user_id' => $user_id])->first();
		if (count($liked_status) == 0) {
			$resultArray['liked'] = "no";
		} else {
			$resultArray['liked'] = "yes";
		}
		$resultArray['video_url'] = $listitem['videourrl'];

		$photos = $this->Photos->find()->where(['item_id' => $item_id])->all();
		$itemCount = 0;
		$resultArray['photos'] = array();
		$itemCount = 0;
		foreach ($photos as $keys => $photo) {
			if ($listitem['id'] == $photo['item_id']) {
				if ($keys == 0) {
					$resultArray['photos'][$itemCount]['item_url_main_70'] = $img_path . 'media/items/thumb70/' . $photo['image_name'];

				} else {
					$resultArray['photos'][$itemCount]['item_url_main_70'] = $img_path . 'media/items/thumb70/' . $photo['image_name'];

				}

				if ($keys == 0) {
					$image = $img_path . 'media/items/thumb350/' . $photo['image_name'];
					list($width, $height) = getimagesize($image);
					$resultArray['photos'][$itemCount]['item_url_main_350'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];
					$resultArray['photos'][$itemCount]['height'] = $height;
					$resultArray['photos'][$itemCount]['width'] = $width;

				} else {
					$image = $img_path . 'media/items/thumb350/' . $photo['image_name'];
					list($width, $height) = getimagesize($image);
					$resultArray['photos'][$itemCount]['item_url_main_350'] = $img_path . 'media/items/thumb350/' . $photo['image_name'];

					$resultArray['photos'][$itemCount]['height'] = $height;
					$resultArray['photos'][$itemCount]['width'] = $width;

				}

				if ($keys == 0) {
					$resultArray['photos'][$itemCount]['item_url_main_original'] = $img_path . 'media/items/original/' . $photo['image_name'];

				} else {
					$resultArray['photos'][$itemCount]['item_url_main_original'] = $img_path . 'media/items/original/' . $photo['image_name'];

				}

				$itemCount += 1;
			}
		}
		$fashion_data = $this->Fashionusers->find()->where(['itemId' => $item_id])->andWhere(['status' => "YES"])->order(['id' => 'DESC'])->all();
		$resultArray['product_selfies'] = array();
		foreach ($fashion_data as $key => $fashion_datas) {
			$resultArray['product_selfies'][$key]['image_350'] = $img_path . 'media/avatars/thumb350/' . $fashion_datas['userimage'];
			$resultArray['product_selfies'][$key]['image_original'] = $img_path . 'media/avatars/original/' . $fashion_datas['userimage'];
			$resultArray['product_selfies'][$key]['user_id'] = $fashion_datas['user_id'];

			$user_detail1 = $this->Users->find()->where(['id' => $fashion_datas['user_id']])->first();
			$profileimage = $user_detail1['profile_image'];
			if ($profileimage == "") {
				$profileimage = "usrimg.jpg";
			}

			$resultArray['product_selfies'][$key]['user_name'] = $user_detail1['username'];
			$resultArray['product_selfies'][$key]['user_image'] = ($profileimage != '') ? $img_path."media/avatars/thumb70/".$profileimage : $img_path."media/avatars/thumb70/usrimg.jpg";

			//$img_path . 'media/avatars/thumb150/' . $profileimage;
		}

		$Details = $this->Comments->find()->where(['item_id' => $item_id])->order(['id' => 'DESC'])->limit(2);
		$resultArray['recent_comments'] = array();
		foreach ($Details as $key => $details) {
			$resultArray['recent_comments'][$key]['comment_id'] = $details['id'];
			$resultArray['recent_comments'][$key]['comment'] = $details['comments'];
			$resultArray['recent_comments'][$key]['user_id'] = $details['user_id'];
			$user_detail = $this->Users->find()->where(['id' => $details['user_id']])->first();
			$profileimage = $user_detail['profile_image'];
			if ($profileimage == "") {
				$profileimage = "usrimg.jpg";
			}
			$resultArray['recent_comments'][$key]['user_image'] = $img_path . 'media/avatars/thumb150/' . $profileimage;
			$resultArray['recent_comments'][$key]['user_name'] = $user_detail['username'];
			$resultArray['recent_comments'][$key]['full_name'] = $user_detail['first_name'] . ' ' . $user_detail['last_name'];
		}
		$items_data = $this->Items->find('all', array(
			'conditions' => array(
				'Items.shop_id' => $shop_data['id'],
				'Items.id <>' => $item_id,
				'Items.status' => 'publish',
			),
			'limit' => 10,

		))->contain('Forexrates');
		$items_data1 = $this->Items->find('all', array(
			'conditions' => array(
				'Items.category_id' => $listitem['category_id'],
				'Items.id <>' => $item_id,
				'Items.status' => 'publish',
			),
			'limit' => 10,

		))->contain('Forexrates');

		$items_fav_data = $this->Itemfavs->find()->where(['user_id' => $user_id])->all();
		if (count($items_fav_data) > 0) {
			foreach ($items_fav_data as $favitems) {
				$favitems_ids[] = $favitems['item_id'];
			}
		} else {
			$favitems_ids = array();
		}

		$sellerData = $this->Users->find()->where(['id' => $listitem['user_id']])->first();
		$sellerAvgRate = $this->getsellerAverage($listitem['user_id']);
		$resultArray['average_rating'] = $sellerAvgRate['rating'];

		$resultArray['store_products'] = $this->convertJsonHome($items_data, $favitems_ids, $user_id, 1);
		$resultArray['similar_products'] = $this->convertJsonHome($items_data1, $favitems_ids, $user_id, 1);

		$inputArray = array('item_id'=>$item_id);
		$resultArray['recent_questions'] = $this->getlatestproduct_faq($inputArray);

		//$resultArray['item_reviews'] = $this->getitemreviews($item_id);

		$itemreviewTable = TableRegistry::get('Itemreviews');
		$reviewData = $this->Itemreviews->find('all', array(
				'conditions' => array(
					'itemid' => $item_id
				),
				'limit' => 2,
				'offset' => 0,
				'order' => 'id DESC',
			))->all();


		$reviewCount = $this->Itemreviews->find('all', array(
				'conditions' => array(
					'itemid' => $item_id,
				),
				'order' => 'id DESC',
			))->count();

		$datanewSourceObject = ConnectionManager::get('default');
    	$reviews_s = $datanewSourceObject->execute("SELECT * from fc_itemreviews where itemid=".$item_id." AND reviews!=''")->fetchAll('assoc');

    	//print_r($reviews_s); die;


		$getAvgrat = $this->getAverage($item_id);
		$result = array();


		//$userImage = $img_path . "media/avatars/thumb70/" . $userImage;
		
		foreach($reviewData as $key=>$eachreview)
		{
			$user_data = $this->Users->find()->where(['id' => $eachreview['userid']])->first();
			$result[$key]['user_id'] = $eachreview['userid'];
			$result[$key]['user_name'] = $user_data['username'];
			$result[$key]['user_image'] = ($user_data['profile_image'] != '') ? $img_path . "media/avatars/thumb70/".$user_data['profile_image'] : $img_path . "media/avatars/thumb70/usrimg.jpg";
			$result[$key]['id'] = $eachreview['orderid'];
			$result[$key]['review_title'] = $eachreview['review_title'];
			$result[$key]['rating'] = $eachreview['ratings'];
			$result[$key]['review'] = $eachreview['reviews'];
		}

		$datanewSourceObject = ConnectionManager::get('default');
    	$ratingstmt = $datanewSourceObject->execute("SELECT count(*) as Total, round(ratings) as ratings from fc_itemreviews where itemid=".$item_id." group by ratings order by ratings desc
		")->fetchAll('assoc');

		//echo '<pre>'; print_r($ratingstmt); die;

    	$byrateGroup = $this->group_by("ratings", $ratingstmt);

    	//echo '<pre>'; print_r($byrateGroup); die;
		$rating_count = ($byrateGroup[5][0]['Total']+$byrateGroup[4][0]['Total']+$byrateGroup[3][0]['Total']+$byrateGroup[2][0]['Total']+$byrateGroup[1][0]['Total']);
		
		$five = (empty($byrateGroup[5][0]['Total'])) ? 0 : $byrateGroup[5][0]['Total'] ;
		$four = (empty($byrateGroup[4][0]['Total'])) ? 0 : $byrateGroup[4][0]['Total'] ;
		$three = (empty($byrateGroup[3][0]['Total'])) ? 0 : $byrateGroup[3][0]['Total'] ;
		$two = (empty($byrateGroup[2][0]['Total'])) ? 0 : $byrateGroup[2][0]['Total'] ;
		$one = (empty($byrateGroup[1][0]['Total'])) ? 0 : $byrateGroup[1][0]['Total'] ;

		$avg_rating_ns = ($listitem['avg_rating'] == 0) ? '0' : $listitem['avg_rating'];
		$resultArray['item_reviews'] = array(
			'review_count'=>count($reviews_s),
			'rating'=>$avg_rating_ns,
			'rating_count'=>$rating_count,
			'five'=>$five,
			'four'=>$four,
			'three'=>$three,
			'two'=>$two,
			'one'=>$one,
			'result'=>$result
			);
		
		$orderstable = TableRegistry::get('Orders');
		$orderitemstable = TableRegistry::get('OrderItems');

		$ordersModel = $orderstable->find('all')->where(['userid' => $user_id])->order(['orderid DESC'])->all();
		$orderid = array();
        foreach ($ordersModel as $value) {
        	if($value['status'] == 'Delivered' || $value['status'] == 'Paid')
        	{
        		$orderid[] = $value['orderid'];
        	}
        }

        if(!empty($orderid))
        {
        	$orderitemModel = $orderitemstable->find('all')->where(['itemid'=>$item_id,'orderid IN' => $orderid])->order(['orderid DESC'])->first();	
        	$resultArray['order_id'] = (isset($orderitemModel->orderid)) ? $orderitemModel->orderid : '';
        	if(isset($orderitemModel->orderid)){
        		$get_review = TableRegistry::get('Itemreviews');
				$firstreviewData = $this->Itemreviews->find('all', array(
						'conditions' => array(
							'itemid' => $item_id,
							'orderid'=> $orderitemModel->orderid
						),
						'order' => 'id DESC',
					))->first();
				$resultArray['review_id'] = (isset($firstreviewData->id)) ? $firstreviewData->id : '';
        	}
        }else{
        	$resultArray['order_id'] = '';
        	$resultArray['review_id'] = '';
        }
		

		//echo '<pre>'; print_r($orderitemModel); die;

		if (count($resultArray) != 0) {
			$resultArray = json_encode($resultArray);
			echo '{"status":"true","result":' . $resultArray . '}';
			die;
		} else
		echo '{"status":"false","message":"No Item found"}';
		die;
	}


	function affiliateItemList()
	{

		$this->loadModel('Items');
		$offset = 0;
		$limit = 10;
		if (!empty($_POST['offset'])) {
			$offset = $_POST['offset'];
		}
		if (!empty($_POST['limit'])) {
			$limit = $_POST['limit'];
		}
		$sortvalue = 0;
		if(isset($_POST['commision_filter']) && $_POST['commision_filter']!=""){
            $sortvalue = $_POST['commision_filter'];
        }
        
        $sort = explode('-', $sortvalue);

		$items_data = array();
		$favitems_ids = array();

		 if($sortvalue != 0) {
		 	$items_data = $this->Items->find('all', array(
				'conditions' => array(
					'Items.status' => 'publish',
					'Items.affiliate_commission >=' => $sort[0],
					'Items.affiliate_commission <=' => $sort[1],

				),
				'limit' => $limit,
				'offset' => $offset,
				'order' => 'Items.id DESC',
			))->contain('Forexrates');
		 } else {
			$items_data = $this->Items->find('all', array(
				'conditions' => array(
					'Items.status' => 'publish',
					'Items.affiliate_commission <>' => 0,

				),
				'limit' => $limit,
				'offset' => $offset,
				'order' => 'Items.id DESC',
			))->contain('Forexrates');
		}
		//print_r($items_data);die;
		$resultArray = $this->convertJsonHome($items_data, $favitems_ids, $_POST['user_id']);

		if (empty($resultArray) && count($resultArray) == 0) {
			echo '{"status":"false","message":"No data found"}';
			die;
		} else {
			//print_r($resultArray);die;
			echo '{"status":"true","items":' . json_encode($resultArray) . '}';
		}
	}

	// function affiliateshare(){

	// 	$this->loadModel('Items');
	// 	$this->loadModel('Shareproducts');

	// 	$user_id = $_POST['user_id'];
	// 	$item_id = $_POST['affilate_item_id'];


	// 	$shareproducts = TableRegistry::get('Shareproducts')->find()->where(['sender_id' => $user_id])->where(['item_id' => $item_id])->where(['status' => 'visit'])->all();

 //        if(count($shareproducts) == 0) {

 //        $shareproductstable = TableRegistry::get('Shareproducts');
 //        $shareproducts = $shareproductstable->newEntity();
        
 //        $shareproducts->sender_id = $user_id;
 //        $shareproducts->receiver_id = "";
 //        $shareproducts->item_id = $item_id;
 //        $shareproducts->status = 'visit';
 //        $result = $shareproductstable->save($shareproducts);

 //        	echo '{"status":"true","message": "successfully updated"}';

 //        } else {
 //        	echo '{"status":"true","message":"something went to be wrong"}';
 //        }

	// }

}



