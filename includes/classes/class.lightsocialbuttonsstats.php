<?php

class LightSocialButtonsStats extends LightSocialButtons {



  public function last_count(){

    $query = db_select('lightsocialbuttons_stats', 'lsbs')
            ->fields('lsbs', array('shday', 'tot'))
            ->condition('hurl', $this->hash, '=')
            ->orderBy('shday', 'DESC')
            ->range(0,1); 

    $result = $query->execute()->fetchAssoc();

    return $result;
  }

  public function write_log($data){

    unset($data['url']);

    $data['tot'] = $data['facebook']
                 + $data['twitter']
                 + $data['googleplus']
                 + $data['linkedin']
                 + $data['pinterest'];



    $data['hurl']=$this->hash;

    if ($data['nid'] == 0) {
      $data['nid'] == NULL;
    }

    $res = db_merge('lightsocialbuttons_stats')
      ->key(array('hurl' => $this->hash, 'shday' => $data['shday']))
      ->fields($data)
      ->execute();

      return $res;
  }

  public function get_stats($nid){

    $query = db_select('lightsocialbuttons_stats', 'lsbs')
            ->fields('lsbs')
            ->condition('nid', $nid, '=')
            ->orderBy('shday', 'ASC');

    $ex = $query->execute();

    while($obj=$ex->fetchObject()){
      $results[$obj->shday] = $obj;
    }
    
    return $results;         

  }
}