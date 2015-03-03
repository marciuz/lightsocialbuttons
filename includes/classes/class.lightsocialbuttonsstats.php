<?php

class LightSocialButtonsStats extends LightSocialButtons {



  public function find_tot_today(){

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
    $data['hurl']=$this->hash;

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

    dsm((string) $query);        

    $results = $query->execute()->fetchAll();
    
    return $results;         

  }
}