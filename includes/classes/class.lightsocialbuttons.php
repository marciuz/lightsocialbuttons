<?php

class LightSocialButtons {


  public $resource;
  public $resource_encoded;
  public $config;

  public function __construct($resource){

    $this->resource = $resource;
    $this->resource_encoded = urlencode($resource);

  }


  public function get_facebook(){
    $path = "http://graph.facebook.com/fql?q=SELECT%20url%2C%20normalized_url%2C%20share_count%2C%20like_count%2C%20comment_count%2C%20total_count%2Ccommentsbox_count%2C%20comments_fbid%2C%20click_count%20FROM%20link_stat%20WHERE%20url%3D'" . $this->resource_encoded . "'";
    $o= (array) $this->_get($path);

    if (isset($o['data'][0]['total_count'])) {
      return $o['data'][0]['total_count'];
    }  
    else {
      return false;
    }

  }

  public function get_twitter(){

    $path = "http://cdn.api.twitter.com/1/urls/count.json?url=".$this->resource_encoded;
    $o= $this->_get($path);
    if (isset($o['count'])) {
      return $o['count'];
    }  
    else {
      return false;
    }
  }

  public function get_googleplus(){

    $data_post = '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' 
          .$this->resource
          .'","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]';
    
    $path = 'https://clients6.google.com/rpc';
    $o = $this->_post($path, $data_post);

    if (isset( $o[0]['result']['metadata']['globalCounts']['count'] )) {
      return intval($o[0]['result']['metadata']['globalCounts']['count']);
    }
    else {
      return false;
    }
    
  }

  public function get_pinterest(){

    $path = "http://api.pinterest.com/v1/urls/count.json?url=".$this->resource_encoded;
    $return_data = $this->_get($path, true);
    $json = preg_replace('/^receiveCount\((.*)\)$/', "\\1", $return_data);
    
    $o = json_decode($json, true);

    if(isset($o['count'])){
      return $o['count'];
    }  
    else{
      return false;
    }
  }

  public function get_linkedin(){

    $path='https://www.linkedin.com/countserv/count/share?format=json&url='.$this->resource_encoded;
    $o= $this->_get($path);

    if(isset($o['count'])){
      return $o['count'];
    }  
    else{
      return false;
    }

  }

  private function _post($url, $post_fields, $raw=false){

    $options = array(
      'method' => 'POST',
      'data' => $post_fields,
      'timeout' => 15,
      'headers' => array('Content-Type' => 'Content-type: application/json'),
    );

    $rq = drupal_http_request($url, $options);
    $result = (isset($rq->data)) ? $rq->data : NULL;

    if ($raw) {
      return $result;
    }
    else {
      return json_decode($result, true);  
    }
  }

  private function _get($url, $raw=false){

    $options = array(
      'method' => 'GET',
      'timeout' => 15,
      'headers' => array('Content-Type' => 'Content-type: application/json'),
    );

    $rq = drupal_http_request($url, $options);
    $result = (isset($rq->data)) ? $rq->data : NULL;

    if ($raw) {
      return $result;
    }
    else {
      return json_decode($result, true);  
    }
  }

  public function get_cache($not_expired=true){

    $hash = md5($this->resource);

    $cache_time = intval(variable_get('lightsocialbuttons_settings_cache_expire', 1800));

    $max_time = (time() - $cache_time);

    $query = db_select('lightsocialbuttons', 'lsb')
            ->fields('lsb')
            ->condition('hurl', $hash, '=');

    if($not_expired){
      $query->condition('last_timestamp', $max_time, '>');
    }
            

    $result = $query->execute()->fetchAssoc();

    return $result;        
  }

  public function set_cache($share_data, $nid=null){

    $hash = md5($this->resource);

    foreach($share_data as $k=>$v){
      $data[$k]=(int) $v;
    }

    $data['tot'] = array_sum($share_data);
    $data['url']=$this->resource;
    $data['last_timestamp']=time();
    $data['hurl']=$hash;
    if(intval($nid)>0) $data['nid']=$nid;

    $res = db_merge('lightsocialbuttons')
      ->key(array('hurl' => $hash))
      ->fields($data)
      ->execute();

    return $res;

  }

  public static function showk($n, $is_active=true){

    if(!$is_active) {
      return $n;
    }

    $n=intval($n);
    
    if($n>=1000){
      return round($n/1000,1)."k";
    }
    else{
      return $n;
    }
  }

  public function get_counts_all($nid=null){

    $data['facebook'] = $this->get_facebook();
    $data['twitter'] = $this->get_twitter();
    $data['googleplus'] = $this->get_googleplus();
    $data['linkedin'] = $this->get_linkedin();
    $data['pinterest'] = $this->get_pinterest();

    return $this->set_cache($data, $nid);
  }
}


