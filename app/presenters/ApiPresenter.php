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
    const URL_MISTNOST = 'http://chat.chatujme.cz/room-info?room_id=%s';

    protected function startup() {
        parent::startup();
    }

    protected function beforeRender() {
        parent::beforeRender();
        $this->terminate();
    }

    public function actionMistnost($id) {
        $return = array('data' => array());

        $cached = $this->cache->load($this->name . $this->action . date('d.m.Y H:i') . $id);
        if ($cached !== NULL) {
            $cached['cached'] = true;
            $this->sendResponse(new \Nette\Application\Responses\JsonResponse($cached, "application/json;charset=utf-8"));
            return;
        }

        $response = $this->tools->callCurlRequest(sprintf(static::URL_MISTNOST, $id));
        if ( preg_match("#>Redirect<#",$response,$r )) {
            $return['data'] = array(
                'message' => "Místnost {$id} mebyla nalezena",
                'code' => 404
            );
            $this->sendResponse(new \Nette\Application\Responses\JsonResponse($return, "application/json;charset=utf-8"));
        }

        preg_match('#<td>Popis</td>\s*<td>(.*?)</td>#im', $response, $r);
        $return['data']['popis'] = $r[1];
        preg_match('#Místnost: <strong>(.+)\s*<br>#im', $response, $r);
        $return['data']['mistnost'] = $r[1];
        preg_match('#<td>Stálý správce</td>\s*\s*<td>(.*?)</td>#im', str_replace("\n", "", $response), $r);
        preg_match_all('#<a target="_blank" href="http://profil.chatujme.cz/(.*?)">#', $r[1], $r2);
        $return['data']['ss'] = $r2[1];
        preg_match('#<td>Celkový čas místnosti</td>\s*<td>(.+) hod</td>#im', $response, $r);
        $return['data']['celkovy-cas'] = str_replace(",", "", $r[1]);
        preg_match_all('#<td class="activeDay">(.+)</td>#im', $response, $r);
        $return['data']['aktualni-den'] = $r[1][0];
        $return['data']['aktualne-prochatovano'] = $r[1][1];

        $return['data']['limit'] = array('mistnost-limit' => FALSE);
        $return['data']['web'] = '';

        if (preg_match("#<td>Web místnosti</td>.*?href=\"([^\"]*)\"#im", str_replace("\n", "", $response), $r)) {
            $return['data']['web'] = $r[1];
        }

        if (preg_match("#<td>Kategorie :</td>.*?<strong>\(.+glyphicon\-(ok|warning)\-sign.*?limit (\d+) hod.*?</strong></td>#i", str_replace("\n", "", $response), $r)) {
            $limit = array();
            $limit['mistnost-limit'] = true;
            $limit['splneny-limit'] = $r[1] == 'warning' ? FALSE : TRUE;
            $limit['limit-hodin'] = $r[2];
            $return['data']['limit'] = $limit;
        }
        \Tracy\Debugger::$maxLen = 1000;

        preg_match("#<td>Založeno</td>.*?strong>\s\|\s([^\(]*)\s\(#im", str_replace("\n", "", $response), $r);
        $return['data']['zalozeno'] = $r[1];

        $this->cache->save($this->name . $this->action . date('d.m.Y H:i') . $id, $return, array(\Nette\Caching\Cache::EXPIRE => "+5 minutes"));
        $this->sendResponse(new \Nette\Application\Responses\JsonResponse($return, "application/json;charset=utf-8"));
    }

    public function actionSvatky($id) {
        $return = array();

        $cached = $this->cache->load($this->name . $this->action . date('d.m.Y') . $id);
        if ($cached !== NULL) {
            $this->sendResponse(new \Nette\Application\Responses\JsonResponse($cached, "application/json;charset=utf-8"));
            return;
        }
        $response = $this->tools->callCurlRequest(static::URL_SVATKY);

        switch ($id) {
            case "přerevčírem":
            case "predevcirem":
                preg_match('#<td class="td-vdz">P.edev..rem</td>\n.+m. sv.tek.+>(.+)</a>#i', $response, $r);
                $r[1] = iconv("ISO-8859-2", "UTF-8", $r[1]);
                $return['data'] = $r[1];
                $this->cache->save($this->name . $this->action . date('d.m.Y') . $id, $return, array(\Nette\Caching\Cache::EXPIRE => "+1 day"));
                break;

            case "včera":
            case "vcera":
                preg_match('#<td class="td-vdz">P.edev..rem</td>\n.+m. sv.tek.+>(.+)</a>#i', $response, $r);
                $r[1] = iconv("ISO-8859-2", "UTF-8", $r[1]);
                $return['data'] = $r[1];
                $this->cache->save($this->name . $this->action . date('d.m.Y') . $id, $return, array(\Nette\Caching\Cache::EXPIRE => "+1 day"));
                break;

            case "dnes":
                preg_match('#<td class="td-vdz">Dnes</td>\n.+m. sv.tek.+>(.+)</a>#i', $response, $r);
                $r[1] = iconv("ISO-8859-2", "UTF-8", $r[1]);
                $return['data'] = $r[1];
                $this->cache->save($this->name . $this->action . date('d.m.Y') . $id, $return, array(\Nette\Caching\Cache::EXPIRE => "+1 day"));
                break;

            case "zítra":
            case "zitra":
                preg_match('#<td class="td-vdz">Z.tra</td>\n.+m. sv.tek.+>(.+)</a>#i', $response, $r);
                $r[1] = iconv("ISO-8859-2", "UTF-8", $r[1]);
                $return['data'] = $r[1];
                $this->cache->save($this->name . $this->action . date('d.m.Y') . $id, $return, array(\Nette\Caching\Cache::EXPIRE => "+1 day"));
                break;
            default:
                $id = NULL;
                $return['data'] = array();
                preg_match('#<td class="td-vdz">P.edev..rem</td>\n.+m. sv.tek.+>(.+)</a>#i', $response, $r);
                $r[1] = iconv("ISO-8859-2", "UTF-8", $r[1]);
                $return['data']['predevcirem'] = $r[1];

                preg_match('#<td class="td-vdz">Dnes</td>\n.+m. sv.tek.+>(.+)</a>#i', $response, $r);
                $r[1] = iconv("ISO-8859-2", "UTF-8", $r[1]);
                $return['data']['vcera'] = $r[1];

                preg_match('#<td class="td-vdz">Dnes</td>\n.+m. sv.tek.+>(.+)</a>#i', $response, $r);
                $r[1] = iconv("ISO-8859-2", "UTF-8", $r[1]);
                $return['data']['dnes'] = $r[1];

                preg_match('#<td class="td-vdz">Z.tra</td>\n.+m. sv.tek.+>(.+)</a>#i', $response, $r);
                $r[1] = iconv("ISO-8859-2", "UTF-8", $r[1]);
                $return['data']['zitra'] = $r[1];
                $this->cache->save($this->name . $this->action . date('d.m.Y') . $id, $return, array(\Nette\Caching\Cache::EXPIRE => "+1 day"));
        }

        $this->sendResponse(new \Nette\Application\Responses\JsonResponse($return, "application/json;charset=utf-8"));
    }

    public function actionPocasi($id, $mesto, $rec = false) { // api.plbot.lury.cz/pocasi/dnes?kraj=kraj
        if ($mesto === NULL) {
            $mesto = "praha";
        } else {
            $mesto = \Nette\Utils\Strings::webalize($mesto);
        }
        $return = array('data' => array());

        $cached = $this->cache->load($this->name . $this->action . $mesto . date('d.m.Y') . $id);
        if ($cached !== NULL) {
            if ($rec === FALSE) {
                $this->sendResponse(new \Nette\Application\Responses\JsonResponse($cached, "application/json;charset=utf-8"));
                return;
            } else {
                return $cached;
            }
        }
        $response = $this->tools->callCurlRequest(sprintf(static::URL_POCASI, $mesto));

        switch ($id) {
            case 'dnes':

                preg_match('#<span id="title-loc">(.*?)</span>#i', $response, $title);
                preg_match('#<div id="predpoved-dnes".+<div class="info">\s*<p>\s*([^<]+)</p>(.+)id="predpoved-zitra"#i', $response, $r);
                preg_match('#<span class="date">(.*?)</span>#i', $r[0], $date);
                preg_match('#temp.*?value">([^<]*).*?sup">([^<]*)</span>.*?<span class=#i', $r[2], $r2);
                preg_match_all('#temp">([\d-]+).*?sup">([^<]*)</span>.*?dayTime">\s*([^<]*)\s*</span>\s*</div>#im', $r[2], $r3);
                \Tracy\Debugger::$maxLen = 10000;
                $return['data']['datum'] = "{$date[1]}";
                $return['data']['predpoved'] = html_entity_decode($r[1]);
                $return['data']['nyni'] = html_entity_decode("{$r2[1]}{$r2[2]}");
                
                $return['data']['rano'] = '?? °C';
                $return['data']['odpoledne'] = '?? °C';
                $return['data']['vecer'] = '?? °C';
                $return['data']['noc'] = '?? °C';
                
                foreach ( $r3[3] as $key => $doba ) {
                    
                    switch ( $doba ) {
                        case 'Ráno':
                            $return['data']['rano'] = html_entity_decode("{$r3[1][$key]}{$r3[2][$key]}");
                            break;
                        case 'Odpoledne':
                            $return['data']['odpoledne'] = html_entity_decode("{$r3[1][$key]}{$r3[2][$key]}");
                            break;
                        case 'Večer':
                            $return['data']['vecer'] = html_entity_decode("{$r3[1][$key]}{$r3[2][$key]}");
                            break;
                        case 'V Noci':
                            $return['data']['noc'] = html_entity_decode("{$r3[1][$key]}{$r3[2][$key]}");
                            break;
                    }
                    
                }
                
                $return['data']['pro'] = "Pro {$title[1]}";

                $this->cache->save($this->name . $this->action . $mesto . date('d.m.Y') . $id, $return, array(\Nette\Caching\Cache::EXPIRE => "+1 day"));
                break;

            case 'zitra':

                preg_match('#<span id="title-loc">(.*?)</span>#i', $response, $title);
                preg_match('#<div id="predpoved-zitra".+<div class="info">\s*<p>\s*([^<]+)</p>(.+)id="predpoved-pozitri"#i', $response, $r);
                preg_match('#<span class="date">(.*?)</span>#i', $r[0], $date);
                preg_match('#atDay.*?temp.*?value">([-\d]+).*?sup">([^<]+)</span>.*?atNight.*?temp.*?value">([-\d]+).*?sup">([^<]+)</span>#i', $r[0], $r2);
                \Tracy\Debugger::$maxLen = 10000;

                $return['data']['datum'] = "{$date[1]}";
                $return['data']['predpoved'] = html_entity_decode($r[1]);
                $return['data']['den'] = html_entity_decode("{$r2[1]}{$r2[2]}");
                $return['data']['noc'] = html_entity_decode("{$r2[3]}{$r2[4]}");
                $return['data']['pro'] = "Pro {$title[1]}";

                $this->cache->save($this->name . $this->action . $mesto . date('d.m.Y') . $id, $return, array(\Nette\Caching\Cache::EXPIRE => "+1 day"));
                break;

            case 'pozitri':

                preg_match('#<span id="title-loc">(.*?)</span>#i', $response, $title);
                preg_match('#<div id="predpoved-pozitri".*?<div class="info">\s*<p>\s*([^<]+)</p>(.*?)id="predpoved-(.*?)"#i', $response, $r);
                preg_match('#<span class="date">(.*?)</span>#i', $r[0], $date);
                //preg_match( '#atDay.*?temp.*?value">(\d+).*?sup">([^<]+)</span>.*?atNight.*?temp.*?value">(\d+).*?sup">([^<]+)</span>#i', $r[0], $r2 );
                preg_match('#atDay.*?temp.*?value">([-\d]+).*?sup">([^<]+)</span>.*?atNight.*?temp.*?value">([-\d]+).*?sup">([^<]+)</span>#i', $r[0], $r2);
                \Tracy\Debugger::$maxLen = 10000;

                $return['data']['datum'] = "{$date[1]}";
                $return['data']['predpoved'] = html_entity_decode($r[1]);
                $return['data']['den'] = html_entity_decode("{$r2[1]}{$r2[2]}");
                $return['data']['noc'] = html_entity_decode("{$r2[3]}{$r2[4]}");
                $return['data']['pro'] = "Pro {$title[1]}";

                $this->cache->save($this->name . $this->action . $mesto . date('d.m.Y') . $id, $return, array(\Nette\Caching\Cache::EXPIRE => "+1 day"));
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
                $this->cache->save($this->name . $this->action . $mesto . date('d.m.Y') . $id, $return, array(\Nette\Caching\Cache::EXPIRE => "+1 day"));
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
        if ($id === NULL) {
            $this->sendResponse(new \Nette\Application\Responses\JsonResponse(array('message' => 'Neni zadano znameni'), "application/json;charset=utf-8"));
            return;
        } else {
            $id = \Nette\Utils\Strings::webalize($id);
        }

        $cached = null; //$this->cache->load( $this->name.$this->action.date('d.m.Y').$id );
        if ($cached !== NULL) {
            $this->sendResponse(new \Nette\Application\Responses\JsonResponse($cached, "application/json;charset=utf-8"));
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


        $this->sendResponse(new \Nette\Application\Responses\JsonResponse($return, "application/json;charset=utf-8"));
    }

    public function actionTV($id, $kdy) {

        $data = $this->getEPGTV();
        $data = array_change_key_case($data, CASE_LOWER);
        $return = array('data' => array());
        $id = str_replace(" ", "-", $id);
        $id = strtolower($id);

        switch ($id) {
            case 'vse':

                switch ($kdy) {

                    case NULL:
                    case 'nyní':
                    case 'nyni':
                        foreach ($data as $nazev => $stanice) {
                            $nazev = str_replace(" ", "-", $nazev);
                            foreach ($stanice as $program) {
                                if ($program['zacatek']->getTimestamp() <= time() && $program['konec']->getTimestamp() >= time()) {
                                    if (!isset($return['data'][$nazev])) {
                                        $return['data'][$nazev] = array();
                                    }
                                    $program['zacatek-full'] = $program['zacatek']->format("d.m.Y H:i");
                                    $program['konec-full'] = $program['konec']->format("d.m.Y H:i");
                                    $program['zacatek'] = $program['zacatek']->format("H:i");
                                    $program['konec'] = $program['konec']->format("H:i");
                                    $return['data'][$nazev][] = $program;
                                }
                                continue;
                            }
                            $program = array();
                            if ( !isset($return['data'][$nazev]) ) {
                                $return['data'][$nazev] = array();
                                $program['program'] = '??';
                                $program['zacatek-full'] = '??';
                                $program['konec-full'] = '??';
                                $program['zacatek'] = '??';
                                $program['konec'] = '??';
                                $return['data'][$nazev][] = $program;
                            }                        
                        } 
                        break;

                    //default:
                    //    break;
                } //switch end
                break;

            case NULL:
            case 'seznam-stanic':

                foreach ($data as $nazev => $stanice) {
                    $nazev = str_replace(" ", "-", $nazev);
                    $return['data'][$nazev] = $this->link("//this", array(
                        'id' => urlencode($nazev),
                        'kdy' => 'nyni'
                    ));
                }

                break;

            default:

                if (!isset($data[$id])) {
                    $return['message'] = "Stanice {$id} neexistuje v seznamu televizí";
                    $this->sendResponse(new \Nette\Application\Responses\JsonResponse($return, "application/json;charset=utf-8"));
                }

                switch ($kdy) {

                    case NULL:
                    case 'nyní':
                    case 'nyni':

                        foreach ($data[$id] as $program) {
                            if ($program['zacatek']->getTimestamp() <= time() && $program['konec']->getTimestamp() >= time()) {
                                $program['zacatek-full'] = $program['zacatek']->format("d.m.Y H:i");
                                $program['konec-full'] = $program['konec']->format("d.m.Y H:i");
                                $program['zacatek'] = $program['zacatek']->format("H:i");
                                $program['konec'] = $program['konec']->format("H:i");
                                $program['stanice'] = $id;
                                $return['data'] = $program;
                            }
                            continue;
                        }

                        break;
                }

                break;
        }

        $this->sendResponse(new \Nette\Application\Responses\JsonResponse($return, "application/json;charset=utf-8"));
    }

    public function getEPGTV() {

        $epg = $this->cache->load('TVPRG' . date('d.m.y H'));
        if ($epg !== NULL) {
            //$this->cache->save( 'TVXML'.date('d.m.y'), $xml );
            return $epg;
        }

        $xml = $this->tools->callCurlRequest('http://televize.sh.cvut.cz/xmltv/all.xml');
        $tv = new \XMLTV($xml);
        $tv = $tv->getXMLTV();

        $stanice = array();
        foreach ($tv->channel as $chann) {
            $chann = (array) $chann;
            $stanice[$chann["@attributes"]["id"]] = $chann["display-name"];
        }
        $programmes = array();
        foreach ((array) $tv as $k => $v) {
            if ($k == 'programme') {
                foreach ($v as $p) {
                    $p = (array) $p;
                    $channelCode = isset($stanice[$p["@attributes"]['channel']]) ? $stanice[$p["@attributes"]['channel']] : $p["@attributes"]['channel'];

                    if (!isset($programmes[$channelCode])) {
                        $programmes[$channelCode] = array();
                    }

                    $p = (array) $p;
                    $data = array(
                        'program' => (string) $p['title'],
                        'popis' => (string) (@$p['sub-title'] . @$p['desc']),
                        'zacatek' => \DateTime::createFromFormat("YmdHis T", (string) $p["@attributes"]['start']),
                        'konec' => \DateTime::createFromFormat("YmdHis T", (string) $p["@attributes"]['stop']),
                            //'popis' => $p['desc'],
                    );
                    $programmes[$channelCode][] = $data;
                }
                break;
            }
        }
        $this->cache->save('TVPRG' . date('d.m.y H'), $programmes);
        return $programmes;
    }

    //DEMO
    public function actionFunkce($id) {
        $return = array();

        $cached = $this->cache->load($this->name . $this->action . date('d.m.Y') . $id);
        if ($cached !== NULL) {
            $this->sendResponse(new \Nette\Application\Responses\JsonResponse($cached, "application/json;charset=utf-8"));
            return;
        }
        $response = $this->tools->callCurlRequest(static::URL_POCASI);

        switch ($id) {
            case 'dnes':

                $this->cache->save($this->name . $this->action . date('d.m.Y') . $id, $return, array(\Nette\Caching\Cache::EXPIRE => "+1 day"));
                break;

            case 'zitra':

                $this->cache->save($this->name . $this->action . date('d.m.Y') . $id, $return, array(\Nette\Caching\Cache::EXPIRE => "+1 day"));
                break;

            default:
                $id = NULL;

                $this->cache->save($this->name . $this->action . date('d.m.Y') . $id, $return, array(\Nette\Caching\Cache::EXPIRE => "+1 day"));
                break;
        }

        $this->sendResponse(new \Nette\Application\Responses\JsonResponse($return, "application/json;charset=utf-8"));
    }

}
