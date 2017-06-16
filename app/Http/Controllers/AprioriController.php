<?php

namespace App\Http\Controllers;

use App\Apriori;
use App\RedisKey;
use Illuminate\Http\Request;

class AprioriController extends Controller
{
    public function __construct()
    {
        $this->middleware('oauth');
        $this->middleware('oauth-user');
        $this->middleware('authorize:'.__CLASS__);
    }

    /**
     * @param Request $request
     * @param int     $id
     *
     * @return mixed
     */
    public function recommend(Request $request, $id)
    {
        if (isset($request->query()['items'])) {
            $apriori = $this->setApriori($request, $id);

            try {
                $items = $request->items;

                natsort($items);

                $rules = $apriori->predictions($items, true);
            } catch (\InvalidArgumentException $ex) {
                return $this->error($ex->getMessage(), 422);
            }

            return $this->success($rules, 200);
        }

        return $this->error("Ups! We couldn't retrieve any reccomendations, please check the 'items' parameter.", 422);
    }

    /**
     * @param Request $request
     * @param int     $id
     *
     * @return mixed
     */
    public function support(Request $request, int $id)
    {
        try {
            if (isset($request->query()['items'])) {
                $apriori = $this->setApriori($request, $id);

                return $this->success([
                    'support' => $apriori->getSupport($request->items),
                ], 200);
            }
        } catch (\InvalidArgumentException $ex) {
            return $this->error($ex->getMessage(), 422);
        }
    }

    /**
     * @param Request $request
     * @param int     $id
     *
     * @return mixed
     */
    public function frequency(Request $request, int $id)
    {
        try {
            if (isset($request->query()['items'])) {
                $apriori = $this->setApriori($request, $id);

                return $this->success([
                    'frequency' => $apriori->getFrequency($request->items),
                ], 200);
            }
        } catch (\InvalidArgumentException $ex) {
            return $this->error($ex->getMessage(), 422);
        }
    }

    /**
     * @param int     $id
     *
     * @return mixed
     */
    public function total(int $id)
    {
        $redisKey = RedisKey::find($id);
    
        if(!is_null($redisKey)){
    
            $apriori = new Apriori($redisKey->combinations_key, $redisKey->transactions_key);
    
            return $this->success([
                'transaction count' => $apriori->getTransactionCount(),
            ], 200);
            
        }
        
        return $this->error("Ups! We couldn't retrieve any reccomendations, please check the 'items' parameter.", 422);
    }

    /**
     * @param Request $request
     * @param int     $id
     *
     * @return Apriori
     */
    private function setApriori(Request $request, int $id) : Apriori
    {
        $this->validate($request, [
            'items.*' => 'required',
        ]);

        $redisKey = RedisKey::find($id);

        return new Apriori($redisKey->combinations_key, $redisKey->transactions_key);
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function isAuthorized(Request $request)
    {
        $resource = 'redis_keys';

        $redis_key = RedisKey::find($this->getArgs($request)['id']);

        return $this->authorizeUser($request, $resource, $redis_key);
    }
}
