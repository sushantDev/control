<?php
use App\Models\ColocationComponent;
use App\Models\Country;
use App\Models\EmailComponent;
use App\Models\Ip;
use App\Models\Rate;
use App\Models\VpsComponent;
use App\Models\WebComponent;

/**
 * @param $value
 * @param string $dash
 * @return string
 */
function display($value, $dash = 'NA')
{
    if (empty($value))
    {
        return $dash;
    }

    return $value;
}

/**
 * @param $width
 * @param null $username
 * @return mixed
 * @internal param $guard
 */
function user_avatar($width, $username = null)
{
    if ($username)
    {
        $user = \App\Models\User::whereUsername($username)->first();
    }
    else
    {
        $user = auth()->user();
    }

    if ($image = $user->image)
    {
        return asset($image->thumbnail($width, $width));
    }
    else
    {
        return asset(config('paths.placeholder.avatar'));
    }
}

/**
 * @param $width
 * @param null $entity
 * @return mixed
 */
function thumbnail($width, $entity = null)
{
    if ( ! is_null($entity))
    {
        if ($image = $entity->image)
        {
            return asset($image->thumbnail($width, $width));
        }
    }

    return asset(config('paths.placeholder.default'));
}

/**
 * @return mixed
 */
function data_centers()
{
    return App\Models\DataCenter::pluck('name', 'id');
}

/**
 * @return mixed
 */
function operating_systems()
{
    return App\Models\OperatingSystem::active()->pluck('name', 'id');
}

/**
 * @return mixed
 */
function license_type()
{
    return App\Models\LicenseType::pluck('name', 'id');
}

/**
 * @return mixed
 */
function ssl_product()
{
    return App\Models\SslProductType::pluck('name', 'id');
}

function customers()
{
    return App\Models\Customer::pluck('username', 'id');
}

function vps_provisions()
{
    return App\Models\VpsProvision::pluck('name', 'id');
}

function users()
{
    return App\Models\User::pluck('username', 'id');
}

function super_notifiables()
{
    $users = App\Models\User::whereHas('roles', function ($query)
    {
        $query->where('slug', 'super');
    })->get();

    return $users;
}

function system_notifiables()
{
    $users = App\Models\User::whereHas('roles', function ($query)
    {
        $query->whereIn('slug', [ 'super', 'support' ]);
    })->get();

    return $users;
}

function expiry_notifiables()
{
    $users = App\Models\User::whereHas('roles', function ($query)
    {
        $query->whereIn('slug', [ 'admin', 'account', 'support', 'sales' ]);
    })->get();

    return $users;
}

function suspend_notifiables()
{
    $users = App\Models\User::whereHas('roles', function ($query)
    {
        $query->whereIn('slug', [ 'admin', 'account', 'sales', 'support' ]);
    })->get();

    return $users;
}

function primary_notifiables()
{
    $users = App\Models\User::whereHas('roles', function ($query)
    {
        $query->whereIn('slug', [ 'admin', 'account' ]);
    })->get();

    return $users;
}

function secondary_notifiables()
{
    $users = App\Models\User::whereHas('roles', function ($query)
    {
        $query->whereIn('slug', [ 'support', 'account', 'sales' ]);
    })->get();

    return $users;
}

function backup_email_notifiables()
{
    $mail = App\Models\User::whereHas('roles', function ($query)
    {
        $query->where('slug', 'super');
    })->first();

    $mail->forceFill([
        'email' => 'backupemail@accessworld.net'
    ]);

    return $mail;
}

/**
 * @return array
 */
function currencies()
{
    return [ 'NPR' => 'NPR', 'USD' => 'USD' ];
}

/**
 * @return array
 */
function vms()
{
    $vms = [];
    foreach (config('xenapi') as $vm => $value)
    {
        if ($value['ACTIVE'])
        {
            $vms[ $vm ] = $value['URL'];
        }
    }

    return $vms;
}

/**
 * @return mixed
 */
function ips()
{
    return Ip::used(false)->pluck('ip', 'ip');
}

/**
 * @param $query
 * @return mixed
 */
function setting($query)
{
    $setting = \App\Models\Setting::fetch($query)->first();

    return $setting ? $setting->value : null;
}

/**
 * @return mixed
 */
function country()
{
    if (is_null(Session::get('country')))
    {
        Session::put('country', get_geo_code());
    }

    $code = Session::get('country');

    return Country::where('iso_alpha2', $code)->firstOrFail();
}

/**
 * @return mixed
 */
function supported_countries()
{
    return Country::where('is_supported', 1)->get();
}

/**
 * @return mixed
 */
function get_geo_code()
{
    $default = config('geoip.default_location');

    //    $geo = GeoIPFacade::getLocation();
    //
    //    if (supported_countries()->where('iso_alpha2', $geo['isoCode'])->first())
    //    {
    //        return $geo['isoCode'];
    //    }

    return $default['isoCode'];
}

function get_vps_base_price($inputs)
{
    $options = [
        'cpu'           => $inputs['cpu'],
        'ram'           => $inputs['ram'],
        'disk'          => $inputs['disk'],
        'traffic'       => $inputs['traffic'],
        'additional-ip' => array_key_exists('additional_ip', $inputs) ? $inputs['additional_ip'] : 0,
        'is-managed'    => array_key_exists('is_managed', $inputs) && $inputs['is_managed'] == "true" ? 1 : 0
    ];
    $price   = 0;
    foreach ($options as $option => $value)
    {
        $price += VpsComponent::whereSlug($option)->first()->price * $value;
    }

    return (float) $price;
}

function get_co_location_base_price($inputs)
{
    $options = [
        'ats-unit' => $inputs['ats']
    ];
    $price   = 0;
    foreach ($options as $option => $value)
    {
        $price += ColocationComponent::whereSlug($option)->first()->price * $value;
    }

    return (float) $price;
}

function get_currency_equivalent($npr, $currency)
{
    if (strtoupper($currency) == "NPR")
    {
        return $npr;
    }
    else
    {
        $rate = Rate::orderBy('created_at', 'desc')->first()->rate;

        return floatval($npr / $rate) * floatval(setting('usd_multiplier'));
    }
}

function get_web_base_price($inputs)
{
    $options = [
        'domain'  => $inputs['domain'],
        'disk'    => $inputs['disk'],
        'traffic' => $inputs['traffic']
    ];
    $price   = 0;
    foreach ($options as $option => $value)
    {
        $price += WebComponent::whereSlug($option)->firstOrFail()->price * $value;
    }

    $price += WebComponent::whereSlug('compute')->firstOrFail()->price;

    return (float) $price;
}

function get_email_base_price($inputs)
{
    $options = [
        'domain'  => $inputs['domain'],
        'disk'    => $inputs['disk'],
        'traffic' => $inputs['traffic']
    ];
    $price   = 0;
    foreach ($options as $option => $value)
    {
        $price += EmailComponent::whereSlug($option)->firstOrFail()->price * $value;
    }

    return (float) $price;
}

function convert_currency($amount, $from, $to)
{
    $data = file_get_contents("https://www.google.com/finance/converter?a=$amount&from=$from&to=$to");
    preg_match("/<span class=bld>(.*)<\/span>/", $data, $converted);
    $converted = preg_replace("/[^0-9.]/", "", $converted[1]);

    return round($converted, 4);
}

/**
 * Transform data if it containains empty values for various orders
 * @param $data
 * @return array
 */
function transform_data($data)
{
    $data = array_map(function ($value)
    {
        return empty($value) ? 0 : $value;
    }, $data);

    return $data;
}

function ping($ip)
{
    exec("ping -c 1 -w 1 $ip", $outcome, $status);

    if (0 == $status)
    {
        return 1;
    }
    else
    {
        return 0;
    }
}

function vpsComponents()
{
    return [ 'cpu', 'ram', 'disk', 'traffic' ];
}

function webComponents()
{
    return [ 'domain', 'disk', 'traffic' ];
}

function emailComponents()
{
    return [ 'domain', 'disk', 'traffic' ];
}

/**
 * @param null $date
 * @return string
 */
function formatDate($date = null)
{
    if ($date)
    {
        return \Carbon\Carbon::createFromFormat('Y-m-d', $date)->format(config('website.date_format'));
    }

    return '-';
}

/**
 * @param $order
 * @param $price
 * @return string
 */
function currencyNumber($order, $price)
{
    return $order->currency . ' ' . number_format($price);
}