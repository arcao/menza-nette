<?php
namespace App\Tasks;


use App\Services\MealMenu;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

class IndexerTask
{
    const PLACES = array(
        'http://skm.zcu.cz/jidelnicek-menzy-bory.html',
        'http://skm.zcu.cz/jidelnicek-menzy-kollarova.html',
        'http://skm.zcu.cz/bufet-kolej-bolevecka.html',
        'http://skm.zcu.cz/bufet-univerzitni-22.html',
        'http://skm.zcu.cz/bufet-pf-klatovska-51.html'
    );

    /**
     * @var MealMenu */
    private $mealMenuService;

    public function __construct(MealMenu $mealMenuService)
    {
        $this->mealMenuService = $mealMenuService;
    }

    /**
     * @cronner-task Parse meal menu at Menza Bory from skm.zcu.cz
     */
    public function parseMenzaBory()
    {
        $this->parse(1);
    }

    /**
     * @cronner-task Parse meal menu at Menza Kollarova from skm.zcu.cz
     */
    public function parseMenzaKollarova()
    {
        $this->parse(2);
    }

    /**
     * @cronner-task Parse Buffet menu at Bolevecka dormitory from skm.zcu.cz
     */
    public function parseBuffetBolevecka()
    {
        $this->parse(3);
    }

    /**
     * @cronner-task Parse Buffet menu at Univerzitni 22 (PST) from skm.zcu.cz
     */
    public function parseBuffetUniverzitni22()
    {
        $this->parse(4);
    }

    /**
     * @cronner-task Parse Buffet menu at faculty of Law from skm.zcu.cz
     */
    public function parseBuffetFacultyOfLaw()
    {
        $this->parse(5);
    }

    // -----------------------------------------------------------------------------------------------------------------

    private function parse($placeId)
    {
        $today = mktime(0,0,0,Date('m'), Date('d'), Date('Y'));
        for ($i=0; $i<10; $i++) {
            $date = $today + $i*DateTime::DAY;
            $this->parseAndStorePlace($placeId, $date);
        }
    }

    private function parseAndStorePlace($placeId, $date = null)
    {
        if (is_null($date))
            $date = time();

        $data = self::parsePlace($placeId, $date);
        if (count($data) > 0)
            $this->mealMenuService->store($placeId, $date, $data);
    }

    private static function parsePlace($placeId, $date = null)
    {
        Validators::assert($placeId, 'integer|0..'.count(self::PLACES), 'placeId');

        if (is_null($date))
            $date = time();

        $datePlain = (is_int($placeId)) ? date('Ymd', $date) : $date;

        $url = self::PLACES[$placeId - 1].'?d='. $datePlain;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        curl_close($ch);

        // convert content to UTF-8
        $content = iconv('windows-1250', 'utf-8', $content);

        $type = '';
        $result = array();

        if (preg_match_all('#<tr(?P<foodtr>.+(?=</tr))</tr>#isUu', $content, $foodList, PREG_SET_ORDER))
        {
            foreach ($foodList AS $foodItem)
            {
                if (preg_match('#<td><h2>(?P<type>[^<]+)</h2></td>#isu', $foodItem['foodtr'], $a))
                {
                    $type = trim($a['type']);
                    continue;
                }

                if (preg_match('#<td>(?P<premium><span style=\'color:red\'>\*)?\s*(?P<name>[^<]+)(</span>)?</td><td class="price">(?P<price_zcu>\d+),-</td><td class="price">(?P<price_zam>\d+),-</td><td class="price1">(?P<price_ext>\d+),-</td>#isu', $foodItem['foodtr'], $a)) {
                    $allergens = self::getAllergens($a['name']);
                    $name = self::getNamePlain($a['name']);
                    $premium = isset($a['premium']) && $a['premium'];

                    $result[$type][] = array(
                        'name'=>$name,
                        'priceStudent'=>$a['price_zcu'],
                        'priceStaff'=>$a['price_zam'],
                        'priceExternal'=>$a['price_ext'],
                        'date'=>$date,
                        'hash'=>self::hash($name),
                        'safeName'=>Strings::webalize($name),
                        'allergens'=>$allergens,
                        'premium'=>$premium);
                }
            }
        }

        return $result;
    }

    private static function getAllergens($input) {
        if (preg_match('#([\d\s,\.]+)$#u', $input, $m)) {
            $allergens = preg_replace('#\s#u', '', $m[1]);
            if ($allergens) {
                return array_map('intval', array_values(array_filter(explode(',', $allergens), 'is_numeric')));
            }
        }

        return Array();
    }

    private static function getNamePlain($name) {
        // remove text "bufet-" at the beginning
        $name = preg_replace('#^bufet\s*-\s*#iu', '', $name);
        // remove allergens at the end
        $name = preg_replace('#[\d\s,\.]+\.?$#u', '', $name);
        return $name;
    }


    public static function hash($input) {
        $hash = Strings::toAscii($input);
        $hash = Str_Replace(' ', '', $hash);
        $hash = md5($hash);

        return $hash.'-'.base64_encode($input);
    }
}
