<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class SeedBarrios extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->UpdateBarrios();
    }

    private function UpdateBarrios()
    {


       /// $Provider =  App::make(\CivicApp\Providers\MapperProvider::class);
        //$AppProvider = App::make(\CivicApp\Providers\AppServiceProvider::class);
        //$Provider->register();
        //$AppProvider->register();

        /** @var \CivicApp\DAL\Catalog\CatalogRepository  $catalogRepository */
        $catalogRepository = App::make(\CivicApp\DAL\Catalog\CatalogRepository::class);


        $html=file_get_contents("http://nuestraciudad.info/portal/Portal:Tabla_de_barrios_de_Córdoba");
        /** @var Collection $barrios */
        $barrios = App::make(Collection::class);
        // $html= $this->getHTML("http://nuestraciudad.info/portal/Portal:Tabla_de_barrios_de_Córdoba",10);
        // preg_match("/<td class='smwtype_wpg'>(.*)</td>/", $html, $match);
        $tagname= 'td class="smwtype_wpg"';
        // preg_match( "/\<{$tagname}\>(.*)\<\/{'td'}\>/", $html, $matches );
        //  $title = $match[1];
        libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $classname= 'smwtype_wpg';
        $url= '/portal/Barrio';
        //$nodes = $xpath->query("//a[contains(@class, '$classname')]");
        $nodes = $xpath->query("//a[contains(@href, '$url')]");
        foreach($nodes as $node)
        {

            $barrio = App::make(\CivicApp\Entities\MapItem\Barrio::class);

            $barrio->name = str_replace('Barrio', '', $node->nodeValue);
            $barrio->name = str_replace('barrio', '', $barrio->name);
            $barrio->name = trim($barrio->name);
            $location = App::make(\CivicApp\Entities\Common\GeoPoint::class);

            $urlBarrio = $node->attributes['href']->value;
            $newUrl = "http://nuestraciudad.info".$urlBarrio;
            $html=file_get_contents($newUrl);
            $dom = new DOMDocument;
            $dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            $nodesBarrio = $xpath->query("//b[text()='Coordenadas']");
            //$nodes[0]->parentNode->parentNode->childNodes[2]->nodeValue
            if(isset($nodesBarrio) && !is_null($nodesBarrio) &&
                !is_null($nodesBarrio[0])) {
                $latSplit  = explode(' ',
                    trim(explode(',', $nodesBarrio[0]->parentNode->parentNode->childNodes[2]->nodeValue)[0]));
                $longSplit = explode(' ',
                    trim(explode(',', $nodesBarrio[0]->parentNode->parentNode->childNodes[2]->nodeValue)[1]));

                $latSplit[0] = str_replace('°', '', $latSplit[0]);
                $latSplit[1] = str_replace("'", "", $latSplit[1]);
                $latSplit[2] = str_replace('"', "", $latSplit[2]);

                $negativeLatFlg = false;
                if (strpos($latSplit[0], '-') !== false) {
                    $negativeLatFlg = true;
                    $latSplit[0]    = str_replace('-', '', $latSplit[0]);
                }

                $lat = ( $negativeLatFlg ? '-' : '' ) . ( $latSplit[0] + $latSplit[1] / 60 + $latSplit[2] / 3600 );

                $longSplit[0] = str_replace('°', '', $longSplit[0]);
                $longSplit[1] = str_replace("'", "", $longSplit[1]);
                $longSplit[2] = str_replace('"', "", $longSplit[2]);

                $negativeLongFlg = false;
                if (strpos($longSplit[0], '-') !== false) {
                    $negativeLongFlg = true;
                    $longSplit[0]    = str_replace('-', '', $longSplit[0]);
                }

                $long = ( $negativeLongFlg ? '-' : '' ) . ( $longSplit[0] + $longSplit[1] / 60 + $longSplit[2] / 3600 );

                $location->location = $lat . ',' . $long;
                $barrio->location   = $location;


            }
            $barrioDB =  $catalogRepository->FindBarrio($barrio->name);

            if(is_null($barrioDB))
                $catalogRepository->AddBarrio($barrio);
            else {
                $barrio->id = $barrioDB->id;
                $catalogRepository->UpdateBarrio($barrio);
            }
            $barrios->push($barrio);
            //   var_dump($lat.','.$long);

        }



    }
}
