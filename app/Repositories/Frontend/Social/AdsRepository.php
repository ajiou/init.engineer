<?php

namespace App\Repositories\Frontend\Social;

use Carbon\Carbon;
use App\Models\Social\Ads;
use App\Models\Social\Cards;
use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use App\Exceptions\GeneralException;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class AdsRepository.
 */
class AdsRepository extends BaseRepository
{
    /**
     * AdsRepository constructor.
     *
     * @param Ads $model
     */
    public function __construct(Ads $model)
    {
        $this->model = $model;
    }

    /**
     * @param int    $paged
     * @param string $orderBy
     * @param string $sort
     *
     * @return mixed
     */
    public function getActivePaginated($paged = 25, $orderBy = 'created_at', $sort = 'desc') : LengthAwarePaginator
    {
        return $this->model
            ->where('started_at', '<=', Carbon::now())
            ->where('end_at', '>=', Carbon::now())
            ->active()
            ->orderBy($orderBy, $sort)
            ->paginate($paged);
    }

    /**
     * @param $id
     *
     * @throws GeneralException
     * @return mixed
     */
    public function findById($id)
    {
        $ads = $this->model
            ->find($id);

        if ($ads instanceof $this->model) {
            return $ads;
        }

        // throw new GeneralException(__('exceptions.frontend.social.cards.ads.not_found'));
        return false;
    }

    /**
     * @throws GeneralException
     * @return mixed
     */
    public function findRandom()
    {
        $ads = $this->model
            ->where('started_at', '<=', Carbon::now())
            ->where('end_at', '>=', Carbon::now())
            ->active()
            ->get();

        if (count($ads) == 0)
        {
            return false;
        }

        $responseAds = false;
        $incidence = 0;
        $rand = rand(0, 10000);
        foreach ($ads as $ad)
        {
            if ($rand >= $incidence && $rand <= $incidence + $ad->incidence)
            {
                $responseAds = $ad;
            }

            $incidence += $ad->incidence;
        }

        return $responseAds;
    }

    /**
     * @param Ads   $ads
     * @param Cards $cards
     *
     * @throws GeneralException
     * @throws \Exception
     * @throws \Throwable
     * @return Ads
     */
    public function add(Ads $ads, Cards $cards) : Ads
    {
        $options = json_decode($ads->options, true);
        array_push($options['cards'], $cards->id);

        return DB::transaction(function () use ($ads, $options) {
            if ($ads->update([
                'number_count' => $ads->number_count + 1,
                'options'      => isset($options)? json_encode($options) : $ads->options,
            ])) {
                // event(new CardsAdsAdded($ads));

                return $ads;
            }

            throw new GeneralException(__('exceptions.frontend.social.cards.ads.added_error'));
        });
    }
}
