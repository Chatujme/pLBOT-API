<?php
namespace App\Presenters;
/**
 * Description of ApiPresenter
 *
 * @author LuRy <lury@lury.cz>, <lukyrys@gmail.com>
 */
class ApiPresenter extends BasePresenter {
    
    const URL_SVATKY = 'http://svatky.pavucina.com/svatek-vcera-dnes-zitra.html';
    const URL_POCASI = 'http://pocasi.seznam.cz/%s';
    const URL_HOROSKOPY = 'http://www.horoskopy.cz/%s';
    
    protected function startup() {
        parent::startup();
    }
    
    protected function beforeRender() {
        parent::beforeRender();
        $this->terminate();
    }
    
    public function actionSvatky($id) {
        $return = array();
        
        $cached = $this->cache->load( $this->name.$this->action.date('d.m.Y').$id );
        if ( $cached !== NULL ) {
            $this->sendResponse( new \Nette\Application\Responses\JsonResponse($cached, "application/json;charset=utf-8" ) );
            return;
        }
        $response = $this->tools->callCurlRequest(static::URL_SVATKY);
        
        switch ( $id ) {
            case "přerevčírem":
            case "predevcirem":
                preg_match('#<td class="td-vdz">P.edev..rem</td>\n.+m. sv.tek.+>(.+)</a>#i',$response,$r);
                $r[1] = iconv("ISO-8859-2","UTF-8",$r[1]);
                $return['data'] = $r[1];
                $this->cache->save( $this->name.$this->action.date('d.m.Y').$id , $return, array( \Nette\Caching\Cache::EXPIRE => "+1 day" ));
                break;

            case "včera":
            case "vcera":
                preg_match('#<td class="td-vdz">P.edev..rem</td>\n.+m. sv.tek.+>(.+)</a>#i',$response,$r);
                $r[1] = iconv("ISO-8859-2","UTF-8",$r[1]);
                $return['data'] = $r[1];
                $this->cache->save( $this->name.$this->action.date('d.m.Y').$id , $return, array( \Nette\Caching\Cache::EXPIRE => "+1 day" ));
                break;

            case "dnes":
                preg_match('#<td class="td-vdz">Dnes</td>\n.+m. sv.tek.+>(.+)</a>#i',$response,$r);
                $r[1] = iconv("ISO-8859-2","UTF-8",$r[1]);
                $return['data'] = $r[1];
                $this->cache->save( $this->name.$this->action.date('d.m.Y').$id , $return, array( \Nette\Caching\Cache::EXPIRE => "+1 day" ));
                break;
            
            case "zítra":
            case "zitra":
                preg_match('#<td class="td-vdz">Z.itra</td>\n.+m. sv.tek.+>(.+)</a>#i',$response,$r);
                $r[1] = iconv("ISO-8859-2","UTF-8",$r[1]);
                $return['data'] = $r[1];
                $this->cache->save( $this->name.$this->action.date('d.m.Y').$id , $return, array( \Nette\Caching\Cache::EXPIRE => "+1 day" ));
                break;
            default:
                $id = NULL;
                $return['data'] = array();
                preg_match('#<td class="td-vdz">P.edev..rem</td>\n.+m. sv.tek.+>(.+)</a>#i',$response,$r);
                $r[1] = iconv("ISO-8859-2","UTF-8",$r[1]);
                $return['data']['predevcirem'] = $r[1];
                
                preg_match('#<td class="td-vdz">Dnes</td>\n.+m. sv.tek.+>(.+)</a>#i',$response,$r);
                $r[1] = iconv("ISO-8859-2","UTF-8",$r[1]);
                $return['data']['vcera'] = $r[1];

                preg_match('#<td class="td-vdz">Dnes</td>\n.+m. sv.tek.+>(.+)</a>#i',$response,$r);
                $r[1] = iconv("ISO-8859-2","UTF-8",$r[1]);
                $return['data']['dnes'] = $r[1];
                
                preg_match('#<td class="td-vdz">Z.tra</td>\n.+m. sv.tek.+>(.+)</a>#i',$response,$r);
                $r[1] = iconv("ISO-8859-2","UTF-8",$r[1]);
                $return['data']['zitra'] = $r[1];
                $this->cache->save( $this->name.$this->action.date('d.m.Y').$id , $return, array( \Nette\Caching\Cache::EXPIRE => "+1 day" ));
        }
        
        $this->sendResponse( new \Nette\Application\Responses\JsonResponse($return, "application/json;charset=utf-8" ) );
        
    }
    
    
    public function actionPocasi($id, $mesto, $rec= false) { // api.plbot.lury.cz/pocasi/dnes?kraj=kraj
        if ( $mesto === NULL ) {
            $mesto = "praha";
        } else {
            $mesto = \Nette\Utils\Strings::webalize($mesto);
        }
        $return = array( 'data' => array() );
        
        $cached = $this->cache->load( $this->name.$this->action.$mesto.date('d.m.Y').$id );
        if ( $cached !== NULL ) {
            if ( $rec === FALSE ) {
                $this->sendResponse( new \Nette\Application\Responses\JsonResponse($cached, "application/json;charset=utf-8" ) );
                return;
            } else {
                return $cached;
            }
        }
        $response = $this->tools->callCurlRequest(sprintf( static::URL_POCASI, $mesto ));
        
        switch ( $id ) {
            case 'dnes':
                
                preg_match('#<span id="title-loc">(.*?)</span>#i',$response,$title);
                preg_match('#<div id="predpoved-dnes".+<div class="info">\s*<p>\s*([^<]+)</p>(.+)id="predpoved-zitra"#i',$response,$r);
                preg_match('#<span class="date">(.*?)</span>#i',$r[0],$date);
                preg_match( '#temp.*?value">([^<]*).*?sup">([^<]*)</span>.*?<span class=#i', $r[2], $r2 );
                preg_match_all( '#temp">(\d+).*?sup">([^<]*)</span>.*?dayTime">\s*([^<]*)\s*</span>\s*</div>#im', $r[2], $r3 );
                \Tracy\Debugger::$maxLen = 10000;
                $return['data']['datum'] = "{$date[1]}";
                $return['data']['predpoved'] = html_entity_decode($r[1]);
                $return['data']['nyni'] = html_entity_decode("{$r2[1]}{$r2[2]}");
                $return['data']['rano'] = html_entity_decode("{$r3[1][0]}{$r3[2][0]}");
                $return['data']['odpoledne'] = html_entity_decode("{$r3[1][1]}{$r3[2][1]}");
                $return['data']['vecer'] = html_entity_decode("{$r3[1][2]}{$r3[2][2]}");
                $return['data']['noc'] = html_entity_decode("{$r3[1][3]}{$r3[2][3]}");
                $return['data']['pro'] = "Pro {$title[1]}";
                
                $this->cache->save( $this->name.$this->action.$mesto.date('d.m.Y').$id , $return, array( \Nette\Caching\Cache::EXPIRE => "+1 day" ));
                break;
            
            case 'zitra':
                
                preg_match('#<span id="title-loc">(.*?)</span>#i',$response,$title);
                preg_match('#<div id="predpoved-zitra".+<div class="info">\s*<p>\s*([^<]+)</p>(.+)id="predpoved-pozitri"#i',$response,$r);
                preg_match('#<span class="date">(.*?)</span>#i',$r[0],$date);
                preg_match( '#atDay.*?temp.*?value">([-\d]+).*?sup">([^<]+)</span>.*?atNight.*?temp.*?value">([-\d]+).*?sup">([^<]+)</span>#i', $r[0], $r2 );
                \Tracy\Debugger::$maxLen = 10000;
                
                $return['data']['datum'] = "{$date[1]}";
                $return['data']['predpoved'] = html_entity_decode($r[1]);
                $return['data']['den'] = html_entity_decode("{$r2[1]}{$r2[2]}");
                $return['data']['noc'] = html_entity_decode("{$r2[3]}{$r2[4]}");
                $return['data']['pro'] = "Pro {$title[1]}";

                $this->cache->save( $this->name.$this->action.$mesto.date('d.m.Y').$id , $return, array( \Nette\Caching\Cache::EXPIRE => "+1 day" ));
                break;
            
            case 'pozitri':
                
                preg_match('#<span id="title-loc">(.*?)</span>#i',$response,$title);
                preg_match('#<div id="predpoved-pozitri".*?<div class="info">\s*<p>\s*([^<]+)</p>(.*?)id="predpoved-(.*?)"#i',$response,$r);
                preg_match('#<span class="date">(.*?)</span>#i',$r[0],$date);
                //preg_match( '#atDay.*?temp.*?value">(\d+).*?sup">([^<]+)</span>.*?atNight.*?temp.*?value">(\d+).*?sup">([^<]+)</span>#i', $r[0], $r2 );
                preg_match( '#atDay.*?temp.*?value">([-\d]+).*?sup">([^<]+)</span>.*?atNight.*?temp.*?value">([-\d]+).*?sup">([^<]+)</span>#i', $r[0], $r2 );
                \Tracy\Debugger::$maxLen = 10000;

                $return['data']['datum'] = "{$date[1]}";
                $return['data']['predpoved'] = html_entity_decode($r[1]);
                $return['data']['den'] = html_entity_decode("{$r2[1]}{$r2[2]}");
                $return['data']['noc'] = html_entity_decode("{$r2[3]}{$r2[4]}");
                $return['data']['pro'] = "Pro {$title[1]}";

                $this->cache->save( $this->name.$this->action.$mesto.date('d.m.Y').$id , $return, array( \Nette\Caching\Cache::EXPIRE => "+1 day" ));
                break;
            
            default:
                $id = NULL;
                
                $dnes = $this->actionPocasi("dnes", $mesto, true);
                $zitra = $this->actionPocasi("zitra", $mesto, true);
                $pozitri = $this->actionPocasi("pozitri", $mesto, true);
                
                $return['data'] = array(
                    'dnes' => $dnes,
                    'zitra' => $zitra,
                    'pozitri' => $pozitri
                );
                $this->cache->save( $this->name.$this->action.$mesto.date('d.m.Y').$id , $return, array( \Nette\Caching\Cache::EXPIRE => "+1 day" ));
                break;
            
            
        }
        if ($rec === false) {
            $this->sendResponse(new \Nette\Application\Responses\JsonResponse($return, "application/json;charset=utf-8"));
        } else {
            return $return['data'];
        }
    }
    

    
    public function actionHoroskop($id) {
        $return = array();
        if ( $id === NULL ) {
            $this->sendResponse( new \Nette\Application\Responses\JsonResponse(array('message' => 'Neni zadano znameni'), "application/json;charset=utf-8" ) );
            return;
        } else {
            $id = \Nette\Utils\Strings::webalize($id);
        }
        
        $cached = null;//$this->cache->load( $this->name.$this->action.date('d.m.Y').$id );
        if ( $cached !== NULL ) {
            $this->sendResponse( new \Nette\Application\Responses\JsonResponse($cached, "application/json;charset=utf-8" ) );
            return;
        }
        $response = $this->tools->callCurlRequest(sprintf(static::URL_HOROSKOPY, $id));
        $match = preg_match("#<h1>(.*?)</h1>.*?<div.*?date\">(.*?)</div>.*?<h2>(.*?)</h2>.*?<p>\s*(.*?)\s*</p>.*?<div.*?>(.*?)</div>.*?<p>(.*?)</p>.*?<div.*?>(.*?)</div>.*?<p>(.*?)</p>.*?<div.*?>(.*?)</div>.*?<p>(.*?)</p>.*?<div.*?>(.*?)</div>.*?<p>(.*?)</p>.*?<div.*?>(.*?)</div>.*?<p>(.*?)</p>#i", $response, $r1);

        if ($match) {
            $return['data'] = array(
                'znameni' => $r1[1],
                'datum' => trim($r1[2]),
                'horoskop' => $r1[4],
                'laska-a-pratelstvi' => $r1[6],
                'penize-a-prace' => $r1[8],
                'rodina-a-vztahy' => $r1[10],
                'zdravi-a-kondice' => $r1[12],
                'vhodne-aktivity-na-dnes' => $r1[14],
            );
        } else {
            $return['data'] = array(
                'message' => "Failed to load {$this->action} for {$id}"
            );
        }

        if ($match) {
            $this->cache->save($this->name . $this->action . date('d.m.Y') . $id, $return, array(\Nette\Caching\Cache::EXPIRE => "+1 day"));
        }


        $this->sendResponse( new \Nette\Application\Responses\JsonResponse($return, "application/json;charset=utf-8" ) );
    }
    
    
    
    
    
    //DEMO
    public function actionFunkce($id) {
        $return = array();
        
        $cached = $this->cache->load( $this->name.$this->action.date('d.m.Y').$id );
        if ( $cached !== NULL ) {
            $this->sendResponse( new \Nette\Application\Responses\JsonResponse($cached, "application/json;charset=utf-8" ) );
            return;
        }
        $response = $this->tools->callCurlRequest(static::URL_POCASI);
        
        switch ( $id ) {
            case 'dnes':
                
                $this->cache->save( $this->name.$this->action.date('d.m.Y').$id , $return, array( \Nette\Caching\Cache::EXPIRE => "+1 day" ));
                break;
            
            case 'zitra':
                
                $this->cache->save( $this->name.$this->action.date('d.m.Y').$id , $return, array( \Nette\Caching\Cache::EXPIRE => "+1 day" ));
                break;
            
            default:
                $id = NULL;
                
                $this->cache->save( $this->name.$this->action.date('d.m.Y').$id , $return, array( \Nette\Caching\Cache::EXPIRE => "+1 day" ));
                break;
            
            
        }
        
        $this->sendResponse( new \Nette\Application\Responses\JsonResponse($return, "application/json;charset=utf-8" ) );
    }
    
}
