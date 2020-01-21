<?php

namespace App\Presenters;

/**
 * Description of ApiPresenter
 *
 * @author LuRy <lury@lury.cz>, <lukyrys@gmail.com>
 */
class ApiPresenter extends BasePresenter {

    const URL_SVATKY = 'https://svatky.pavucina.com/svatek-vcera-dnes-zitra.html';
    const URL_POCASI = 'https://pocasi-backend.centrum.cz/api/v2/widget/welcome/%s';
    const URL_HOROSKOPY = 'https://www.horoskopy.cz/%s';
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
                'message' => "Místnost {$id} nebyla nalezena",
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
        //$response = str_replace(["\n", "\r"], "", $this->tools->callCurlRequest(static::URL_SVATKY));
        $response = $this->tools->callCurlRequest(static::URL_SVATKY);

        switch ($id) {
            case "přerevčírem":
            case "predevcirem":
                preg_match('#<td class="td-vdz">P.edev..rem</td>\n.+má svátek.+>(.+)</a>#i', $response, $r);
                if ( !count($r) ) {
                    preg_match('#<td class="td-vdz">P.edev..rem</td>\n.+<td\s*class="td-jmeno">([^<]*)</td>#i', $response, $r);
                }
                //$r[1] = iconv("ISO-8859-2", "UTF-8", $r[1]);
                $return['data'] = $r[1];
                $this->cache->save($this->name . $this->action . date('d.m.Y') . $id, $return, array(\Nette\Caching\Cache::EXPIRE => "+1 day"));
                break;

            case "včera":
            case "vcera":
                preg_match('#<td class="td-vdz">V.era</td>\n.+má svátek.+>(.+)</a>#i', $response, $r);
                if ( !count($r) ) {
                    preg_match('#<td class="td-vdz">V.era</td>\n.+<td\s*class="td-jmeno">([^<]*)</td>#i', $response, $r);
                }
                //$r[1] = iconv("ISO-8859-2", "UTF-8", $r[1]);
                $return['data'] = $r[1];
                $this->cache->save($this->name . $this->action . date('d.m.Y') . $id, $return, array(\Nette\Caching\Cache::EXPIRE => "+1 day"));
                break;

            case "dnes":
                preg_match('#<td class="td-vdz">Dnes</td>\n.+má svátek.+>(.+)</a>#i', $response, $r);
                if ( !count($r) ) {
                    preg_match('#<td class="td-vdz">Dnes</td>\n.+<td\s*class="td-jmeno">([^<]*)</td>#i', $response, $r);
                }
                //$r[1] = iconv("ISO-8859-2", "UTF-8", $r[1]);
                $return['data'] = $r[1];
                $this->cache->save($this->name . $this->action . date('d.m.Y') . $id, $return, array(\Nette\Caching\Cache::EXPIRE => "+1 day"));
                break;

            case "zítra":
            case "zitra":
                preg_match('#<td class="td-vdz">Z.tra</td>\n.+má svátek.+>(.+)</a>#i', $response, $r);
                if ( !count($r) ) {
                    preg_match('#<td class="td-vdz">Z.tra</td>\n.+<td\s*class="td-jmeno">([^<]*)</td>#i', $response, $r);
                }
                //$r[1] = iconv("ISO-8859-2", "UTF-8", $r[1]);
                $return['data'] = $r[1];
                $this->cache->save($this->name . $this->action . date('d.m.Y') . $id, $return, array(\Nette\Caching\Cache::EXPIRE => "+1 day"));
                break;
            default:
                $id = NULL;
                $return['data'] = array();
                preg_match('#<td class="td-vdz">Předevčírem</td>\n.+má svátek.+>(.+)</a>#uim', $response, $r);
                if ( !count($r) ) {
                    preg_match('#<td class="td-vdz">P.edev..rem</td>\n.+<td\s*class="td-jmeno">([^<]*)</td>#i', $response, $r);
                }
                //$r[1] = iconv("ISO-8859-2", "UTF-8", $r[1]);
                $return['data']['predevcirem'] = $r[1];
                
                preg_match('#<td class="td-vdz">Včera</td>\n.+má svátek.+>(.+)</a>#i', $response, $r);
                if ( !count($r) ) {
                    preg_match('#<td class="td-vdz">Včera</td>\n.+<td\s*class="td-jmeno">([^<]*)</td>#i', $response, $r);
                }
                //$r[1] = iconv("ISO-8859-2", "UTF-8", $r[1]);
                $return['data']['vcera'] = $r[1];

                preg_match('#<th>Dnes</th>\n.+má svátek.+>(.+)</a>#i', $response, $r);
                if ( !count($r) ) {
                    preg_match('#<th>Dnes</th>\n.+<td\s*class="td-jmeno">([^<]*)</td>#i', $response, $r);
                }
                //$r[1] = iconv("ISO-8859-2", "UTF-8", $r[1]);
                $return['data']['dnes'] = $r[1];

                preg_match('#<td class="td-vdz">Zítra</td>\n.+má svátek.+>(.+)</a>#i', $response, $r);
                if ( !count($r) ) {
                    preg_match('#<td class="td-vdz">Zítra</td>\n.+<td\s*class="td-jmeno">([^<]*)</td>#i', $response, $r);
                }
                //$r[1] = iconv("ISO-8859-2", "UTF-8", $r[1]);
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
        $response = \Nette\Utils\ArrayHash::from(json_decode($this->tools->callCurlRequest(sprintf(static::URL_POCASI, $mesto))), true);

        switch ($id) {
            case 'dnes':
                $data = $response['long_term_forecast']->forecasts[0];
                break;

            case 'zitra':
                $data = $response['long_term_forecast']->forecasts[1];
                break;

            case 'pozitri':
                $data = $response['long_term_forecast']->forecasts[2];
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

        if ( $id !== NULL ) {
            $return['data']['datum'] = $data->date;
            $return['data']['predpoved'] = $data->day_forecast;
            $return['data']['nyni'] = $response->welcome[0]->actual->temp;
            
            $return['data']['den'] = $data->temp_day;
            $return['data']['noc'] = $data->temp_night;
            
            $return['data']['pro'] = "Pro {$response->welcome[0]->place->city}";
    
            $this->cache->save($this->name . $this->action . $mesto . date('d.m.Y') . $id, $return, array(\Nette\Caching\Cache::EXPIRE => "+1 day"));
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
                            //$nazev = strtolower(str_replace(" ", "-", iconv("UTF-8", "ASCII//TRANSLIT", $nazev)));
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

        $displayChannelName = [
            "ctd" => "ČT : D", 
            "plus" => "Plus",
            "radiozurnal" => "Radiožurnál",
            "jazz" => "Jazz",
            "dvojka" => "Dvojka",
            "ct1" => "ČT1",
            "vltava" => "Vltava",
            "ct2" => "ČT2",
            "wave" => "Wave",
            "ct24" => "ČT24",
            "dur" => "Dur",
            "ct4" => "ČT SPORT",
            "junior" => "Junior",
            "novacinema" => "Nova Cinema",
            "primacool" => "Prima Cool",
            "nova" => "TV Nova",
            "primafamily" => "Prima",
            "tvbarrandov" => "TV Barrandov",
            "780.dvb.guide" => "Prima Love",
            "1026.dvb.guide" => "1026.dvb.guide",
            "6914.dvb.guide" => "Televize Seznam",
            "proglas" => "Proglas",
            "788.dvb.guide" => "Prima Krimi",
            "2053.dvb.guide" => "Kino Barrandov",
            "primazoom" => "Prima Zoom",
            "slagrtv" => "Šlágr TV",
            "2052.dvb.guide" => "Barrandov Krimi",
            "779.dvb.guide" => "Prima Max",
            "ocko" => "Óčko",
            "2562.dvb.guide" => "JOJ Family",
            "pohoda" => "Pohoda",
            "telka" => "Telka",
            "fanda" => "Fanda",
            "2818.dvb.guide" => "Rebel",
            "smichov" => "Smíchov",
            "801.dvb.guide" => "Prima Comedy Central"
        ];

        $epg = $this->cache->load('TVPRG' . date('d.m.y H'));
        if ($epg !== NULL) {
            //$this->cache->save( 'TVXML'.date('d.m.y'), $xml );
            //return $epg;
        }

        $xml = $this->tools->callCurlRequest('http://xmltv.tvpc.cz/xmltv.xml');
        $tv = new \XMLTV($xml);
        $tv = $tv->getXMLTV();

        $stanice = array();
        foreach ($tv->programme as $chann) {
            $chann = (array) $chann;
            $stanice[$chann["@attributes"]["channel"]] = isset($displayChannelName[$chann["@attributes"]["channel"]]) ? $displayChannelName[$chann["@attributes"]["channel"]] : $chann["@attributes"]["channel"];
            $stanice[$chann["@attributes"]["channel"]] = strtolower(str_replace(" ", "-", iconv("UTF-8", "ASCII//TRANSLIT", $stanice[$chann["@attributes"]["channel"]])));
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
                    if ( is_array($p['title']) ){
                        $p['title'] = $p['title'][0];
                    }
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
