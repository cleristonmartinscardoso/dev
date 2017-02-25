<?php
header('Content-Type: text/plain; charset=utf-8');

require('../conectDB.php');
require('../clear_mod.php');
/*
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_http_input('UTF-8');
mb_language('uni');
mb_regex_encoding('UTF-8');
ob_start('mb_output_handler');*/


class log{
	
	
	
	public function logMsg( $msg, $level = 'info', $file = "/var/log/depuni/bl_update.log" )
{
    // variável que vai armazenar o nível do log (INFO, WARNING ou ERROR)
    $levelStr = '';
 
    // verifica o nível do log
    switch ( $level )
    {
        case 'info':
            // nível de informação
            $levelStr = 'INFO';
            break;
 
        case 'warning':
            // nível de aviso
            $levelStr = 'WARNING';
            break;
 
        case 'error':
            // nível de erro
            $levelStr = 'ERROR';
            break;
    }
 
    // data atual
    $date = date( 'Y-m-d H:i:s' );
 
    // formata a mensagem do log
    // 1o: data atual
    // 2o: nível da mensagem (INFO, WARNING ou ERROR)
    // 3o: a mensagem propriamente dita
    // 4o: uma quebra de linha
    $msg = sprintf( "[%s] [%s]: %s%s", $date, $levelStr, $msg, PHP_EOL );
 
    // escreve o log no arquivo
    // é necessário usar FILE_APPEND para que a mensagem seja escrita no final do arquivo, preservando o conteúdo antigo do arquivo
    file_put_contents( $file, $msg, FILE_APPEND );
}
	
}
class zoho{
	  private $parameter;
	  private $XMLInfo;
	  private $numero_bl;
	  private $zoho_captado_id;
	  private $view;
	  private $parameterBL;
	  private $parameterHis;
	  private $parameterIte;
	  private $parameterDoc;
	  
	  
	  public function check($arr,$logger,$zoho_captado_id){
		$this->zoho_captado_id = $zoho_captado_id;
		//Removendo historico,itens e documentos de dentro do array e definindo nova variavel com as informações do BL
		$arrBL = array_splice($arr, 0, -3);
		$this->numero_bl = $arrBL[numero_bl];  

		
		//Definindo variaveis dos agregados
		$arrHis = $arr[historico];
		$arrIte = $arr[itens];
		$arrDoc = $arr[documentos];
		
		$this->BL($arrBL);//Acionando methodo para criar parametro bl
		//$this->historico($arrHis);//Acionando methodo para criar parametro historico
		//$this->itens($arrIte);//Acionando methodo para criar parametro historico
		//$this->documentos($arrDoc);//Acionando methodo para criar parametro historico
		
		//Verificando se o BL já foi gerado a captação no zoho
		if ($zoho_captado_id != "NULL" and $zoho_captado_id != ' '){
            //Atualizando BL dentro de captaçaõ
            
			$this->XMLInfo="<ZohoCreator>
							<applicationlist>
								<application name='captacao'>
									<viewlist>
										<view name='Lista_Captacao'>
											<update>
												<criteria>
												  ID = $zoho_captado_id
												</criteria>		
													<newvalues>
													  $this->parameterBL
													</newvalues>
											</update>
										</view>
									</viewlist>
								</application>
							</applicationlist>
					</ZohoCreator>";
			
            //Excluindo todos os históricos do BL citado
		    //$this->deleteRecord($this->numero_bl);
			//Regravando dados atualizados	BL dentro da captação
			$this->apiConnect($this->XMLInfo, $this->numero_bl);

            //Adicionando novos históricos, itens e documentos
            $this->XMLInfo="<ZohoCreator>
							<applicationlist>
								<application name='captacao'>
									<formlist>
										<form name='historico'>
											<add>" .
											$this->parameterHis
											. "</add>
										</form>
									</formlist>
									<formlist>
										<form name='itens'>
											<add>" .
											$this->parameterIte
											. "</add>
										</form>
									</formlist>
								</application>
							</applicationlist>
					</ZohoCreator>";
          			

		}else{
			$this->XMLInfo="<ZohoCreator>
							<applicationlist>
								<application name='captacao'>
									<formlist>
										<form name='Lista_De_BL'>
											<add>" .
											$this->parameter
											. "</add>
										</form>
									</formlist>
								</application>
							</applicationlist>
					</ZohoCreator>";
					//Deletando registros no zoho
					//$this->deleteRecord($this->numero_bl);
		}			
		
		//echo $this->XMLInfo;
		//Regravando dados atualizados	
		$this->apiConnect($this->XMLInfo, $this->numero_bl);
	  }
	  
	  private function BL($arr){
			$this->numero_bl = $arr[numero_bl];
			foreach($arr as $key => $value){
				if ($value != null and $value != " "){
					if (preg_match('/created_at/',$key) or preg_match('/data/',$key)){//Verificando campos do tupo data
							if ($value != " " && $value != "00/00/0000 00:00:00" && $value != null){
								if (substr_count($value, ":") == 2 and substr_count($value, "-") >= 2){//Checando se é tipo datetime
									$value = date("d/m/Y H:i:s", strtotime($value));					
								}else{
									if (substr_count($value, "-") >= 2){//Checando se é do tipo date
										$value = date("d/m/Y", strtotime($value));
									}
								}
								
								
								
							}
					}
 
                    //Definindo excessões de campos, marcar apenas os campos que existem no formulario do zoho.  
                    if ($this->zoho_captado_id == "NULL"){
					    $this->parameter .= "<field name='" . $key . "'><value><![CDATA[" . ucwords(strtolower(($value))) . "]]></value></field>";
					}else{
						//Recuperando apenas os campos que existem em captação
						if ($key == "status_carga" || $key == "numero_bl" || $key == "referencia" || $key == "porto_origem" || $key == "descricao_bl" || $key == "data_operacao" || $key == "numero_ce" || $key == "porto_destino" || $key == "numero_dta" || $key == "numero_di" || $key == "porto_destino"){
							$this->parameterBL .= "<field name='" . $key . "'><value><![CDATA[" . ucwords(strtolower(($value))) . "]]></value></field>";
						}
					}
					
				}	
				
				
			}
		}
		
	  private function historico($arr){
			if (count($arr) > 0){
				
					$this->parameter .= '<field name="historico">';
					foreach($arr as $idxHis => $value){
						$this->parameter .= "<add>";
						foreach($arr[$idxHis] as $key => $value){
							if ($value != null and $value != " "){
							
								if (preg_match('/created_at/',$key) or preg_match('/data/',$key)){//Verificando campos do tupo data
										if ($value != " " && $value != "00/00/0000 00:00:00" && $value != null){
											if (substr_count($value, ":") == 2 and substr_count($value, "-") >= 2){//Checando se é tipo datetime
												$value = date("d/m/Y H:i:s", strtotime($value));					
											}else{
												if (substr_count($value, "-") >= 2){//Checando se é do tipo date
													$value = date("d/m/Y", strtotime($value));
												}
											}
											
											
											
										}
								}
								//Verificando se o BL ainda não foi gerado a captação,	 caso negativo, ele incrementa o parametro para atualizar as informações apenas dentro do view Lista_De_BL no Zoho
								if ($this->zoho_captado_id == "NULL"){
									$this->parameter .= "<field name='" . $key . "'>" . $value . "</field>";
									$this->parameter .= "<field name='numero_bl'>" . $this->numero_bl . "</field>";
								}else{//Caso já tenha sido gerado uma captação com esse BL, ele gera um parametro isolado para atualizar apenas o histórico
									$this->parameterHis .= "<field name='" . $key . "'>" . $value . "</field>";
									$this->parameterHis .= "<field name='numero_bl'>" . $this->numero_bl . "</field>";
									$this->parameterHis .= "<field name='Captacao'>" . $this->zoho_captado_id . "</field>";
	
								}
							}	
						}
						$this->parameter .= "</add>";
										 
					}
					$this->parameter .= "</field>";
				}
			}
		
		
	  private function itens($arr){
			if (count($arr) > 0){
				$this->parameter .= '<field name="item">';
				foreach($arr as $idxHis => $value){
					$this->parameter .= "<add>";
					foreach($arr[$idxHis] as $key => $value){
						if ($value != null and $value != " "){
							if (preg_match('/created_at/',$key) or preg_match('/data/',$key)){//Verificando campos do tupo data
									if ($value != " " && $value != "00/00/0000 00:00:00" && $value != null){
										if (substr_count($value, ":") == 2 and substr_count($value, "-") >= 2){//Checando se é tipo datetime
											$value = date("d/m/Y H:i:s", strtotime($value));					
										}else{
											if (substr_count($value, "-") >= 2){//Checando se é do tipo date
												$value = date("d/m/Y", strtotime($value));
											}
										}
										
										
										
									}
							}
							//Verificando se o BL ainda não foi gerado a captação,	 caso negativo, ele incrementa o parametro para atualizar as informações apenas dentro do view Lista_De_BL no Zoho
							if ($this->zoho_captado_id == "NULL"){
								$this->parameter .= "<field name='" . $key . "'>" . $value . "</field>";
								$this->parameter .= "<field name='numero_bl'>" . $this->numero_bl . "</field>";
							}else{//Caso já tenha sido gerado uma captação com esse BL, ele gera um parametro isolado para atualizar apenas o histórico
								$this->parameterIte .= "<field name='" . $key . "'>" . $value . "</field>";
								$this->parameterIte .= "<field name='numero_bl'>" . $this->numero_bl . "</field>";
								$this->parameterIte .= "<field name='Captacao'>" . $this->zoho_captado_id . "</field>";


							}
						}	
					}
					$this->parameter .= "</add>";
									 
				}
				$this->parameter .= "</field>";
			}	
			
		}
		
	  private function documentos($arr){
			if (count($arr) > 0){
				$this->parameter .= '<field name="documento">';
				foreach($arr as $idxHis => $value){
					$this->parameter .= "<add>";
					foreach($arr[$idxHis] as $key => $value){
						if ($value != null and $value != " "){
							if (preg_match('/created_at/',$key) or preg_match('/data/',$key)){//Verificando campos do tupo data
									if ($value != " " && $value != "00/00/0000 00:00:00" && $value != null){
										if (substr_count($value, ":") == 2 and substr_count($value, "-") >= 2){//Checando se é tipo datetime
											$value = date("d/m/Y H:i:s", strtotime($value));					
										}else{
											if (substr_count($value, "-") >= 2){//Checando se é do tipo date
												$value = date("d/m/Y", strtotime($value));
											}
										}
										
										
										
									}
							}
							//Verificando se o BL ainda não foi gerado a captação,	 caso negativo, ele incrementa o parametro para atualizar as informações apenas dentro do view Lista_De_BL no Zoho
							if ($this->zoho_captado_id == "NULL"){
								$this->parameter .= "<field name='" . $key . "'>" . $value . "</field>";
								$this->parameter .= "<field name='numero_bl'>" . $this->numero_bl . "</field>";
							}else{//Caso já tenha sido gerado uma captação com esse BL, ele gera um parametro isolado para atualizar apenas o histórico
								$this->parameterDoc .= "<field name='" . $key . "'>" . $value . "</field>";
								$this->parameterDoc .= "<field name='numero_bl'>" . $this->numero_bl . "</field>";
								$this->parameter .= "<field name='Captacao'>" . $this->zoho_captado_id . "</field>";

							}
						}	
					}
					$this->parameter .= "</add>";
									 
				}
				$this->parameter .= "</field>";
			}	
			
		}
	    
	  private function deleteRecord($numero_bl){
		  $XMLInfo="<ZohoCreator>
						<applicationlist>
							<application name='captacao'>
								<viewlist>
									<view name='Lista_BL'>
										<delete>
											<criteria>
												numero_bl=$numero_bl
											</criteria>
										</delete>
									</view>
									<view name='Historicos_Rep'>
										<delete>
											<criteria>
												numero_bl==$numero_bl
											</criteria>
										</delete>
									</view>
									<view name='Itens_Rep'>
										<delete>
											<criteria>
												numero_bl==$numero_bl
											</criteria>
										</delete>
									</view>
									<view name='Documentos_Rep'>
										<delete>
											<criteria>
												numero_bl==$numero_bl
											</criteria>
										</delete>
									</view>
								</viewlist>
							</application>
						</applicationlist>
					</ZohoCreator>";
					
		    $this->apiConnect($XMLInfo,$numero_bl);
	  }
	  
	  private function apiConnect($XMLInfo,$numero_bl){
			 $path="authtoken=713c15a0119724b23d467f97f5837e4c&zc_ownername=zoho_cyro2&XMLString=$XMLInfo";
	 		 $url = "https://creator.zoho.com/api/xml/write/";
			 $curl = curl_init($url);
			 curl_setopt($curl, CURLOPT_HEADER, false);
			 curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			 curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 4);
			 curl_setopt($curl, CURLOPT_HTTPHEADER,
				   array("Content-type: application/x-www-form-urlencoded"));
			 curl_setopt($curl, CURLOPT_POST, true);
			 curl_setopt($curl, CURLOPT_POSTFIELDS, $path);
			 $json_response = curl_exec($curl); 
			 $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			 curl_close($curl);			
			 if ($status == 200){
			    
				//Checando de gravou com sucesso
				if (!strpos($json_response, "Success")){
				   //$logger->logMsg("Falha ao gravar no zoho - BL - " . $numero_bl);
				}else{
					//Alterando campo zoho_add para YES
					//$sqlUP = "UPDATE bls LEFT JOIN historicos ON historicos.id_bl=bls.id LEFT JOIN documentos ON documentos.id_bl=bls.id LEFT JOIN itens ON itens.id_bl=bls.id SET bls.zoho_add='yes', historicos.zoho_add='yes', documentos.zoho_add='yes',itens.zoho_add='yes',bls.data_captado='" . date("Y-m-d H:i:s") . "' WHERE bls.id=$id_bl";
					//db($conexao,$sqlUP,$email);
					return $json_response;
				}
			 }	
		  
	  }
	
}

class comex{
    private $blLista;
	private $status;

	function addBlList($date){
         $path="sync=$date";
         $url = "http://comex.io/v1/bl/listar?api_key=c847e0a7fca6628a13d71b2fe89c6d41&$path";
         $curl = curl_init($url);
         curl_setopt($curl, CURLOPT_HEADER, false);
         curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 4);
         curl_setopt($curl, CURLOPT_HTTPHEADER,
               array("Content-type: application/x-www-form-urlencoded"));
         curl_setopt($curl, CURLOPT_POST, true);
         //curl_setopt($curl, CURLOPT_POSTFIELDS, $path);
         $json_response = curl_exec($curl);
		 $json_response = json_decode($json_response,true);
		 $this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		 curl_close($curl);
			$log = new log();
			if ($this->status == 200){
				if ($json_response[response_status] == "error"){	
					$log->logMsg("Falha ao solicitar a lista para Comex - Error: " . $this->blLista[response_message], "warning");
					exit();
				}else{
					if ($json_response[lista] == 0){
						$log->logMsg("Lista recebida por Comex esta vazia");
						exit();
					}else{
						$this->blLista = $json_response;
					}
				}	
			}else{
				$log->logMsg("Host Comex respondeu com código:" . $this->status,"warning");
				exit();
			}
			
	}

    function getComexList(){
			return $this->blLista;		
    }
}


class agent{
	public $updateZohoRequire;
	private $zoho_added;
	private $zoho_captado_id;
	
	
	public function check($conn,$arr,$idBL,&$logger){
			$this->zoho_added = "no";
			$this->updateZohoRequire = 0;
            $arrOri = $arr;
		    //Removendo historico,itens e documentos de dentro do array e definindo nova variavel com as informações do BL
			$arrBL = array_splice($arr, 0, -3);
			
			$idBL = $arrBL[id];
			//Definindo variaveis dos agregados
			$arrHis = $arr[historico];
			$arrIte = $arr[itens];
			$arrDoc = $arr[documentos];
			$this->BL($conn,$arrBL,$idBL,$logger);//Acionando methodo para criar parametro bl
			$this->historico($conn,$arrHis,$idBL,$logger);//Acionando methodo para criar parametro historico
			$this->item($conn,$arrIte,$idBL,$logger);//Acionando methodo para criar parametro historico
			$this->documento($conn,$arrDoc,$idBL,$logger);//Acionando methodo para criar parametro historico
	        //Verificando se ha necessidade de atualizar os dados no ZOHO
			if ($this->updateZohoRequire == true and $this->zoho_added == "yes"){
				$zoho = new zoho();
				$zoho->check($arrOri,$logger,$this->zoho_captado_id);
			}
			
		
		
	}
	
	public function BL($conn,$arr,$idBL,&$logger){
		    $logger->logMsg("Checando BL - " . $idBL );
		    $idBL = $arr[id]; 
			$sql = "SELECT COUNT(*) FROM bls WHERE id=$idBL";
			if ($stm = $conn->query($sql)){
				 if ($stm->fetchColumn() == 0){
					$logger->logMsg("Não existe bl cadastrado - Novo Cadastro a seguir");
					$this->insertBL($conn,$arr,$idBL,$logger);
				 }else{
					$logger->logMsg("Existe bl cadastrado");
					$this->updateBL($conn,$arr,$idBL,$logger); 
				 }
			}			
			
		
	}
	
	private function insertBL($conn,$arr,$idBL,$logger){
		    foreach($arr as $key => $value){
				
				if (!is_array($value)){
					$sqlKey .= " `$key`,";
					if ($value == ""){
					   $sqlValue .= " NULL,";
					}else{
					   $sqlValue .= " '$value',";
					}
				}
				
			}
			$sqlKey = $this->removeComma($sqlKey);
			$sqlValue = $this->removeComma($sqlValue);
			$sqlString = "INSERT into `bls` ($sqlKey) VALUES ($sqlValue)";	
			$update = $this->db($conn,$sqlString,$idBL);
			if ($update){
				$logger->logMsg("Novo BL Cadastrado - " . $idBL);
			}
											
	}
	
	private function updateBL($conn,$arr,$idBL,$logger){
			foreach($arr as $key => $value){
				if (!is_array($value)){
					if ($value == ""){
					   $sqlKey .= " `$key`=NULL,";
					}else{
						$sqlKey .= " `$key`='$value',";
					}
				}	
			}
			$sqlKey = $this->removeComma($sqlKey);
			$sqlString = "UPDATE `bls` SET $sqlKey WHERE id=$idBL";
			$update = $this->db($conn,$sqlString,$idBL);
			if ($update){
				$logger->logMsg("BL Atualizada - " . $sqlString);
				$logger->logMsg(" ");
				$this->updateZohoRequire = true;
			}
			$sqlString = "SELECT * FROM `bls` WHERE id=$idBL";
			$stm = $conn->prepare($sqlString);
			if ($stm->execute()){//Verificando se ocorreu com sucesso a busca
			   $result = $stm->fetch();
			   $this->zoho_added = $result[zoho_add];
			   $this->zoho_captado_id = $result[zoho_captado_id];
			}
			   
											
	}
	
	private function historico($conn,$arr,$idBL,$logger){
		    $logger->logMsg("Checando Histórico - BL" . $idBL );
			if (count($arr) == 0){
				$logger->logMsg("Sem histórico - BL" . $idBL );
				return;
			}
		    foreach($arr as $idx => $value){
			        $idHis = $arr[$idx][id];
				    $sql = "SELECT COUNT(*) FROM historicos WHERE id=$idHis";
					if ($stm = $conn->query($sql)){
						 if ($stm->fetchColumn() == 0){
							$logger->logMsg("Não existe histórico cadastrado - Novo Cadastro a seguir");
							$this->insertHistorico($conn,$arr[$idx],$idBL,$idHis,$logger);
						 }else{
							$logger->logMsg("Existe histórico cadastrado - " . $idHis);
							$this->updateHistorico($conn,$arr[$idx],$idBL,$idHis,$logger); 
							 
						 }
					}		
			}
		
	}
	
	private function insertHistorico($conn,$arr,$idBL,$idHis,$logger){
		    foreach($arr as $key => $value){
				$sqlKey .= " `$key`,";
				if ($value == ""){
				   $sqlValue .= " NULL,";
				}else{
 				   $sqlValue .= " '$value',";
				}
			}
			$sqlKey = $this->removeComma($sqlKey);
			$sqlValue = $this->removeComma($sqlValue);
            $sqlString = "INSERT into `historicos` (`id_bl`,$sqlKey) VALUES ($idBL,$sqlValue)";			
			$update = $this->db($conn,$sqlString,$idBL);
			if ($update){
				$logger->logMsg("Novo histórico cadastrado - " . $sqlString);
				$this->updateZohoRequire = true;
			}
	}
	
	private function updateHistorico($conn,$arr,$idBL,$idHis,$logger){
            foreach($arr as $key => $value){
				if ($key != "id"){
					if ($value == ""){
					   $sqlKey .= " `$key`=NULL,";
					}else{
						$sqlKey .= " `$key`='$value',";
					}
				}
			}
				$sqlKey = $this->removeComma($sqlKey);
				$sqlString = "UPDATE `historicos` SET $sqlKey WHERE id=$idHis";
				
				$update = $this->db($conn,$sqlString,$idBL);
				if ($update){
				$logger->logMsg("Histórico atualizado- " . $idHis);
					$this->updateZohoRequire = true;
			    }
				

			
	}
	
	function item($conn,$arr,$idBL,$logger){
		    $logger->logMsg("Checando Itens - BL" . $idBL );
			if (count($arr) == 0){
				$logger->logMsg("Sem item - BL" . $idBL );
				return;
			}
			foreach($arr as $idx => $value){
				$idItem = $arr[$idx][id];
				$sql = "SELECT COUNT(*) FROM itens WHERE id=$idItem";
				if ($stm = $conn->query($sql)){
				    if ($stm->fetchColumn() == 0){
					   $logger->logMsg("Não existe item cadastrado - Novo Cadastro a seguir");
					   $this->insertItem($conn,$arr[$idx],$idBL,$idItem,$logger);
					   }else{
	  					   $logger->logMsg("Existe item cadastrado");
						   $this->updateItem($conn,$arr[$idx],$idBL,$idItem,$logger); 
					   }
					}
				}	
	}
	
	
	private function insertItem($conn,$arr,$idBL,$idItem,$logger){
		    foreach($arr as $key => $value){
				$sqlKey .= " `$key`,";
				if ($value == ""){
				   $sqlValue .= " NULL,";
				}else{
 				   $sqlValue .= " '$value',";
				}
			}
			$sqlKey = $this->removeComma($sqlKey);
			$sqlValue = $this->removeComma($sqlValue);
            $sqlString = "INSERT into `itens` (`id_bl`,$sqlKey) VALUES ($idBL,$sqlValue)";			
			$update = $this->db($conn,$sqlString,$idBL);
			if ($update){
				$logger->logMsg("Novo item cadastrado- " . $idItem);
				$this->updateZohoRequire = true;
			}
										
	}
	
	private function updateItem($conn,$arr,$idBL,$idItem,$logger){
			foreach($arr as $key => $value){
				if ($value == ""){
				   $sqlKey .= " `$key`=NULL,";
				}else{
					$sqlKey .= " `$key`='$value',";
				}
			}
			$sqlKey = $this->removeComma($sqlKey);
			$sqlString = "UPDATE `itens` SET $sqlKey WHERE id=$idItem";
			$update = $this->db($conn,$sqlString,$idBL);
			if ($update){//Verificando se atualizou
   				$logger->logMsg("Item atualizado - " . $idItem);
			    $this->updateZohoRequire = true;
			}
											
	}
	
	
	function documento($conn,$arr,$idBL,$logger){
		$logger->logMsg("Checando documentos - BL" . $idBL );
		if (count($arr) == 0){
			$logger->logMsg("Sem documentos - BL" . $idBL );
			return;
		}
		foreach($arr as $idx => $value){
			$idDoc = $arr[$idx][id];
    		foreach($arr[$idx] as $key => $value){
				if ($value != null){
				   $prepFilter .= " AND $key='$value'";	
				}
			}
			$sql = "SELECT COUNT(*) FROM documentos WHERE id=$idDoc";
			if ($stm = $conn->query($sql)){
			    if ($stm->fetchColumn() == 0){
				   $logger->logMsg("Não existe documento cadastrado - Novo Cadastro a seguir");
				   $this->insertDoc($conn,$arr[$idx],$idBL,$idDoc,$logger);
			    }else{
 					$logger->logMsg("Existe documento cadastrado");
					$this->updateDoc($conn,$arr[$idx],$idBL,$idDoc,$logger); 
				}
			}
		}
	}
	
	private function insertDoc($conn,$arr,$idBL,$logger){
		    foreach($arr as $key => $value){
				$sqlKey .= " `$key`,";
				if ($value == ""){
				   $sqlValue .= " NULL,";
				}else{
 				   $sqlValue .= " '$value',";
				}
			}
			$sqlKey = $this->removeComma($sqlKey);
			$sqlValue = $this->removeComma($sqlValue);
            $sqlString = "INSERT into `documentos` (`id_bl`,$sqlKey) VALUES ($idBL,$sqlValue)";			
			$update = $this->db($conn,$sqlString,$idBL);
			if ($update){
				$logger->logMsg("Novo documento cadastrado- " . $idDoc);
				$this->updateZohoRequire = true;
			}
			
	}
	
	private function updateDoc($conn,$arr,$idBL,$logger){
			foreach($arr as $key => $value){
				if ($value == ""){
				   $sqlKey .= " `$key`=NULL,";
				}else{
					$sqlKey .= " `$key`='$value',";
				}
			}
			$sqlKey = $this->removeComma($sqlKey);
			$sqlString = "UPDATE `documentos` SET $sqlKey WHERE id_bl=$idBL $prepFilter";
			$update = $this->db($conn,$sqlString,$idBL);
			if ($update){//Verificando se atualizou
   				$logger->logMsg("Documento atualizado - " . $idDoc);
			    $this->updateZohoRequire = true;
			}	
			
											
	}
	
	private function removeComma($word){
		    $word = substr($word, 0, -1);
			return $word;
	}
	  
	private function db(PDO $conn,$sqlString,$idBL){
			$log = new log();
            $affected = $conn->exec($sqlString);
			$error = $conn->errorInfo();
			if ($error[0] != 00000){
				$log->logMsg("Falha no banco de dados DB_COMEX " . $error[2], "warning");
			}
			if ($affected == 1){
				//$log->logMsg("Novo registro atualizado - " . $sqlString);
				return true;
			}else{
				return false;
			}
    }
}

$logger = new log();
//Buscando lista
$comex = new comex();
$dta = date("Y-m-d");
$comex->addBlList($dta);
$result = $comex->getComexList();
//echo json_encode($result,true);exit();


//Criando agregados
$agenteBL = new agent();
$agenteHistoricos = new agent();
$agenteItens = new agent();
$agenteDocumentos = new agent();

//Varrendo lista
foreach ($result[lista] as $idxList => $value){
	//echo $result[lista][$idxList][numero_bl] . "<BR>";
	$agenteBL->check($conexao,$result[lista][$idxList],$idBL,$logger);
	$logger->logMsg("");
}

?>