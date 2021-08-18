<?php
	class ReportPoDaysController extends AppController{
		public $helpers = array('Html', 'Form', 'Time','Pdf');
	    public $components = array('Session','Utility','Graphql','UploadFiles','MyCurl');

	    function beforeFilter(){
	        parent::beforeFilter();
	        $this->Auth->allow('login', 'logout');
        	$this->Auth->authError = __('You must be logged in to view this page.');
	        $allow = array('index','index_audit','list_table_audit','list_table','export_pdf','modal_upload','modal_document','delete_file','upload_file','export_pdf_audit');
	        if (!$this->Permission->CheckPermission($this->Auth->user('id'), $this->Auth->user('role_id'), $this->Auth->request->url, $this->name, $allow)) {
	            throw new MethodNotAllowedException();
	        }
	    }

	    function index(){
            $user_id = $this->Auth->user('id');
            $wh = $this->App->fetchAll('SELECT group_warehouses.warehouse_id as id, warehouses.warehname, warehouses.code,  warehouses.warehouse_type_code
            FROM user_groups INNER JOIN group_warehouses ON user_groups.group_id = group_warehouses.group_id
                LEFT OUTER JOIN warehouses ON group_warehouses.warehouse_id = warehouses.id
            WHERE user_groups.user_id='.$user_id.' and warehouses.inuse=true',false);

			$this->set('wh_list', $wh);
        }
        
        function index_audit()
        {
 
        }

	    function list_table_audit(){
	    	$this->layout = 'ajax';

            $date_start = $this->data['date_start'];
            $data_new = array('data' => array('am_inventory_po_day_all',array($date_start),array("date")));
            $data_end = json_encode($data_new);
			
            $data_po = $this->MyCurl->MyCurlStore(Configure::read('URL_NodeAM'),$data_end,'');

            $data_po = json_decode($data_po,true);
            $this->MyCurl->check_alert_back($data_po,$data_new);
			$this->set('datapo',$data_po);
			$this->set('date',$date_start);

        }

        function list_table(){
	    	$this->layout = 'ajax';

	    	$code = $this->data['code'];
            $date_start = $this->data['date_start'];
            $date_end = $this->data['date_end'];
            $wh = $this->data['wh'];
            
            // $date_start = '28/02/2020';
            // $date_end = '28/02/2020';

	    	//convert date_start
	    	$arr_date = explode("/",$date_start);
			$c_date_start = $arr_date[2]."-".$arr_date[1]."-".$arr_date[0];
			
            
            //convert date_end
	    	$arr_date = explode("/",$date_end);
            $c_date_end = $arr_date[2]."-".$arr_date[1]."-".$arr_date[0];

      
            $user_id = $this->Auth->user('id');
            $wh = $this->App->fetchAll('SELECT group_warehouses.warehouse_id as id, warehouses.warehname, warehouses.code,  warehouses.warehouse_type_code
            FROM user_groups INNER JOIN group_warehouses ON user_groups.group_id = group_warehouses.group_id
                LEFT OUTER JOIN warehouses ON group_warehouses.warehouse_id = warehouses.id
            WHERE user_groups.user_id='.$user_id.' and warehouses.inuse=true',false);

            $arr_id_wh = array();

            foreach ($wh as $z => $x) {
                $arr_id_wh[] = $x[0]['id'];
            }

            if(count($arr_id_wh)>0){
                $imp_in_id1 = "'".implode("','", $arr_id_wh)."'";

                $result = $this->dataSource->fetchAll('SELECT DISTINCT
                    inventory_masters.invnumber,
                    inventory_masters.refernumber,
                    inventory_masters.id AS inv_id,
                    date(inventory_masters.datelog),
                    inventory_details.inv_dr,
                    inventory_details.inv_cr,
                    inventory_details.warehouse_id,
                    Coalesce(porders.ponumber,inventory_masters.refernumber,inventory_finishgood_refs.ref_no) AS ref_no,
                    porderdetails.unit,
                    porderdetails.id,
                    Coalesce(products.productid,materials.materialid,services.serviceid) AS product_code,
                    Coalesce(products.name,materials.name,services.name) AS product_name,
                    Coalesce(product_units.name,material_units.name) AS unit_name,
                    warehouses.warehname,
                    warehouses.code,
                    users.fullname

                FROM inventory_masters INNER JOIN inventory_details ON inventory_masters."id" = inventory_details.inventory_master_id
                    LEFT JOIN inventory_finishgood_refs ON inventory_finishgood_refs."fg_no" = inventory_masters.invnumber
                        AND inventory_finishgood_refs.cancel=false
                    LEFT JOIN porders ON porders."id" = inventory_masters.porder_id
                    LEFT JOIN porderdetails ON (porders."id" = porderdetails.porder_id AND porderdetails.cancel=false
                        AND
                        ((porderdetails.product_type=\'P\' AND inventory_details.product_id=porderdetails.product_id)
                        OR
                        (porderdetails.product_type=\'M\' AND inventory_details.material_id=porderdetails.product_id)
                        OR
                        (porderdetails.product_type=\'S\' AND inventory_details.service_id=porderdetails.product_id)))
                    LEFT JOIN products ON products."id" = inventory_details.product_id
                    LEFT JOIN materials ON materials."id" = inventory_details.material_id
                    LEFT JOIN services ON services."id" = inventory_details.service_id

                    LEFT JOIN product_units ON (product_units."id" = inventory_details.unit AND inventory_details.product_id is not null)
                    LEFT JOIN material_units ON (material_units."id" = inventory_details.unit  AND inventory_details.material_id is not null)

                    LEFT JOIN warehouses ON warehouses."id" = inventory_details.warehouse_id
                    LEFT JOIN users ON users."id" = inventory_masters.user_id
                WHERE (typeinven=1 OR typeinven=6 OR typeinven=7 ) and inventory_masters.datelog BETWEEN \''.$c_date_start.'\' AND \''.$c_date_start.'\'AND inventory_details.warehouse_id in('.$imp_in_id1.') AND inventory_masters.cancel=false AND inventory_details.cancel=false 
                ORDER BY inventory_details.warehouse_id,invnumber,product_code ASC
                ',false);

         }
			if ($result) {
				$unique_arr = array();
				foreach ($result as $k => $v) {
					array_push($unique_arr,$v[0]['inv_id']);
					$unique_arr = array_unique($unique_arr);
					$unique_arr = array_values($unique_arr);
				}
				$ref_id = implode($unique_arr,',');
				$count_data = $this->dataSource->fetchAll("SELECT invnumber
				FROM inventory_images 
				LEFT JOIN inventory_masters
				ON inventory_masters.id = inventory_images.ref_id
				WHERE ref_id IN ($ref_id) 
				AND inventory_images.cancel = FALSE 
				GROUP BY ref_id, invnumber
				ORDER BY invnumber ASC");
				if ($count_data) {
					foreach ($count_data as $key => $value) {
						$count_data[$key] = $count_data[$key][0]['invnumber'];
					}
					$this->set('count_doc',$count_data);
				}
            }
			
			$this->set('result',$this->Utility->convert_quot($result));
			$this->set('date_start',$date_start);
			$this->set('date_end',$date_end);
			$this->set('code',$code);
			$this->set('wh',$wh);
	    }

	    function export_pdf(){

            $result = json_decode($this->data['result'],true);
            $date_start =  $this->data['date_start'];

            $this->layout = 'ajax';
			$this->set('result',$result);
			$this->set('date_start',$date_start);
            $this->set('company_name',$this->Utility->get_company());
            $this->set('wh','ทั้งหมด');

        }
        
        function export_pdf_audit($date_start){
	    	$this->layout = 'ajax';

            $data_new = array('data' => array('am_inventory_po_day_all',array($date_start),array("date")));
            $data_end = json_encode($data_new);
            $data_po = $this->MyCurl->MyCurlStore(Configure::read('URL_NodeAM'),$data_end,'');
            $data_po = json_decode($data_po,true);
            $this->MyCurl->check_alert_back($data_po,$data_new);
			$this->set('datapo',$data_po);
			$this->set('date_start',date("d/m/Y", strtotime($date_start)));
            $this->set('company_name',$this->Utility->get_company());

        }

		public function modal_upload($id){
			$this->layout = "ajax";
			$this->set('id', $id);
		}

		public function modal_document($ref_no){
			$this->layout = "ajax";
			$ref_id = $this->dataSource->fetchAll("SELECT id FROM inventory_masters WHERE invnumber = '$ref_no' LIMIT 1"); 
			$ref_id = $ref_id[0][0]['id'];
			//ข้อมูลรายงานที่อัพโหลด
			$query =  ['query'=>"{
				getFiles(ref_id:$ref_id,table:\"inventory_images\"){
					token
					data{
					  id
					  file_name
					  path
					}
				}
			}"];
			$data = $this->Graphql->graphql_query("POST",$query,[],null);
			$data = json_decode($data,true);
			$data = $data['data']['getFiles']['data'];
			$this->set("ref_id",$ref_id);
			$count = count($data);
			$this->set("data",$data);
		}

		public function delete_file($ref_id = null, $img_id  = null){
			$this->layout = "ajax";
			$table = "inventory_images";
			$user_delete = $this->Auth->user('id');
			$query =  ['query'=>"mutation{
				deleteFiles(ref_id:$ref_id,image_id:[$img_id],table:\"$table\",user_delete:$user_delete){
					token
					data{
						message
					}
				}
			}"];
			$message = $this->Graphql->graphql_query("POST",$query,[],null);
			$message = json_decode($message,true);
			$message =  $message['data']['deleteFiles']['data'][0]['message'];
			echo $message;
			exit;
		}

		public function upload_file(){
			$this->layout = "ajax";
			$ref_no = $this->data['ref_no'];
			$id = $this->dataSource->fetchAll("SELECT id FROM inventory_masters WHERE invnumber = '$ref_no' LIMIT 1");
			$ref_id =  $id[0][0]['id'];//Find Ref ID
			$user_id = $this->Auth->user('id');//User ID
			$table = "inventory_images";//Table for store
			$this->UploadFiles->uploading($user_id,$table,$ref_id,$_FILES);
			exit;
		} 
	}
?>
