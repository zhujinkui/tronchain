<?php

namespace think\support;

class TronApi
{
    protected $full_node;
    protected $solidity_node;
    protected $event_node;

    static function mainNet()
    {
        return new self('https://api.trongrid.io');
    }

    static function testNet()
    {
        return new self('https://api.shasta.trongrid.io');
    }

    function __construct($full_node_url, $solidity_node_url = null, $event_node_url = null)
    {
        if (is_null($solidity_node_url)) {
            $solidity_node_url = $full_node_url;
        }

        if (is_null($event_node_url)) {
            $event_node_url = $full_node_url;
        }

        $this->full_node     = new NodeClient($full_node_url);
        $this->solidity_node = new NodeClient($solidity_node_url);
        $this->event_node    = new NodeClient($event_node_url);
    }

    function getNextMaintenanceTime()
    {
        return $this->full_node->get('/wallet/getnextmaintenancetime');
    }

    function timeUntilNextVoteCycle()
    {
        $args = func_get_args();
        return $this->getNextMaintenanceTime(...$args);
    }

    function broadcastTransaction($tx)
    {
        return $this->full_node->post('/wallet/broadcasttransaction', $tx);
    }

    function sendRawTransaction()
    {
        $args = func_get_args();
        return $this->broadcastTransaction(...$args);
    }

    /*
    function createTransaction($to,$amount,$from){
      $payload = [
        'to_address' => Address::decode($to),
        'owner_address' => Address::decode($from),
        'amount' => $amount
      ];
      return $this->full_node->post('/wallet/createtransaction',$payload);
    }  
    */

    function sendTrx()
    {
        $args = func_get_args();
        return $this->createTransaction(...$args);
    }

    function getContractEvents($contractAddress, $since)
    {
        $api     = '/event/contract/' . $contractAddress;
        $payload = ['since' => $since, 'sort' => 'block_timestamp'];
        return $this->event_node->get($api, $payload);
    }

    function getTransactionEvents($txid)
    {
        $api = '/event/transaction/' . $txid;
        return $this->event_node->get($api, []);
    }

    /*
    function triggerSmartContract($contractAddress,$functionSelector,$parameter,$fromAddress,$feeLimit=1000000000,$callValue=0,$bandwidthLimit=0){
      $payload = [
        'contract_address' => Address::decode($contractAddress),
        'function_selector' => $functionSelector,
        'parameter' => $parameter,
        'owner_address' =>  Address::decode($fromAddress),
        'fee_limit'     =>  $feeLimit,
        'call_value'    =>  $callValue,
        'consume_user_resource_percent' =>  $bandwidthLimit,
      ];
      return $this->full_node->post('/wallet/triggersmartcontract', $payload);
    }
    */

    function getAccount($address, $confirmed = true)
    {
        $payload = [
            'address' => Address::decode($address)
        ];
        if ($confirmed) {
            return $this->full_node->get('/wallet/getaccount', $payload);
        } else {
            return $this->solidity_node->get('/walletsolidity/getaccount', $payload);
        }
    }

    function getBalance($address, $confirmed = true)
    {
        $accountInfo = $this->getAccount($address, $confirmed);
        if (!isset($accountInfo->balance)) {
            throw new \Exception('Balance error. Maybe you should send 10 trx to this address to activate it.');
        }
        return $accountInfo->balance;
    }

    function getUncomfirmedBalance($address)
    {
        return $this->getBalance($address, false);
    }

    function getAccountNet($address)
    {
        $payload = [
            'address' => Address::decode($address)
        ];
        return $this->full_node->post('/wallet/getaccountnet', $payload);
    }

    function getBandwidth()
    {
        $args = func_get_args();
        return $this->getAccountNet(...$args);
    }

    function getAccountResource($address)
    {
        $payload = [
            'address' => Address::decode($address)
        ];
        return $this->full_node->post('/wallet/getaccountresource', $payload);
    }

    function getContract($address)
    {
        $payload = [
            'value' => Address::decode($address)
        ];
        return $this->full_node->get('/wallet/getcontract', $payload);
    }

    function getChainParameters()
    {
        return $this->full_node->get('/wallet/getchainparameters', []);
    }

    function getNodeInfo()
    {
        return $this->full_node->get('/wallet/nodeinfo', []);
    }

    function listNodes()
    {
        return $this->full_node->get('/wallet/listnodes', []);
    }

    //get|post?
    function getNowBlock($confirmed = true)
    {
        if ($confirmed) {
            return $this->solidity_node->get('/walletsolidity/getnowblock', []);
        } else {
            return $this->full_node->get('/wallet/getnowblock', []);
        }
    }

    function getCurrentBlock()
    {
        $args = func_get_args();
        return $this->getNowBlock(...$args);
    }

    function getBlockById($hash)
    {
        $payload = [
            'value' => $hash
        ];
        return $this->full_node->post('/wallet/getblockbyid', $payload);
    }

    function getBlockByHash()
    {
        $args = func_get_args();
        return $this->getBlockById(...$args);
    }

    function getBlockByNum($num)
    {
        $payload = [
            'num' => $num
        ];
        return $this->full_node->post('/wallet/getblockbynum', $payload);
    }

    function getBlockByNumber()
    {
        $args = func_get_args();
        return $this->getBlockByNum(...$args);
    }

    function getBlockByLimitNext($start, $end)
    {
        $payload = [
            'startNum' => $start,
            'endNum'   => $end
        ];
        return $this->full_node->get('/wallet/getblockbylimitnext', $payload);
    }

    function getBlockRange()
    {
        $args = func_get_args();
        return $this->getBlockByLimitNext(...$args);
    }

    function getTransactionById($txid, $confirmed = true)
    {
        $payload = [
            'value' => $txid
        ];
        if ($confirmed) {
            return $this->solidity_node->post('/walletsolidity/gettransactionbyid', $payload);
        } else {
            return $this->full_node->post('/wallet/gettransactionbyid', $payload);
        }
    }

    function getTransaction()
    {
        $args = func_get_args();
        return $this->gettransactionbyid(...$args);
    }

    function getConfirmedTransaction()
    {
        $args = func_get_args();
        return $this->getTransactionById(...$args);
    }

    function getTransactionInfoById($txid, $confirmed = true)
    {
        $payload = [
            'value' => $txid
        ];
        if ($confirmed) {
            return $this->solidity_node->post('/walletsolidity/gettransactioninfobyid', $payload);
        } else {
            return $this->full_node->post('/wallet/gettransactioninfobyid', $payload);
        }
    }

    function getTransactionInfo()
    {
        $args = func_get_args();
        return $this->getTransactionInfoById(...$args);
    }

    function getUnconfirmedTransactionInfo($txid)
    {
        return $this->getTransactionInfoById($txid, false);
    }


    //all|from|to
    function getTransactionsByAddress($address, $direction = 'from', $offset = 0, $limit = 30)
    {
        $payload = [
            'account' => [
                'address' => Address::decode($address)
            ],
            'offset'  => $offset,
            'limit'   => $limit
        ];
        $api     = '/walletextension/gettransactions' . $direction . 'this';
        return $this->solidity_node->post($api, $payload);
    }

    function getReward($address, $confirmed = true)
    {
        $payload = [
            'address' => Address::decode($address)
        ];
        if ($confirmed) {
            return $this->solidity_node->post('/walletsolidity/getreward', $payload);
        } else {
            return $this->full_node->post('/wallet/getreward', $payload);
        }
    }

    function getUnconfirmedReward($address)
    {
        return $this->getReward($address, false);
    }

    function getApprovedList($tx)
    {
        return $this->full_node->post('/wallet/getapprovedlist', $tx);
    }

    function getSignWeight($tx)
    {
        return $this->full_node->post('/wallet/getsignweight', $tx);
    }

    function listWitnesses($confirmed = true)
    {
        if ($confirmed) {
            return $this->solidity_node->get('/walletsolidity/listwitnesses', []);
        } else {
            return $this->full_node->get('/walletsolidity/listwitnesses', []);
        }
    }

    function listSuperRepresentatives()
    {
        $args = func_get_args();
        return $this->listWitnesses(...$args);
    }

    /*txbuilder*/
    function createTransaction($to, $amount, $from)
    {
        $payload = [
            'to_address'    => Address::decode($to),
            'owner_address' => Address::decode($from),
            'amount'        => $amount
        ];
        $ret     = $this->full_node->post('/wallet/createtransaction', $payload);
        return $ret;
    }

    function transferAsset($to, $asset, $amount, $from)
    {
        $payload = [
            'to_address'    => Address::decode($to),
            'asset_name'    => bin2hex($asset),
            'amount'        => $amount,
            'owner_address' => Address::decode($from)
        ];
        return $this->full_node->post('/wallet/transferasset', $payload);
    }

    function sendAsset()
    {
        $args = func_get_args();
        return $this->transferAsset(...$args);
    }

    function sendToken()
    {
        $args = func_get_args();
        return $this->transferAsset(...$args);
    }

    function createAssetIssue($name, $abbr, $desc, $url, $supply, $trx_ratio, $token_ratio, $start, $end, $limit, $public_limit, $frozen_amount, $frozen_days, $precision, $from)
    {
        $payload = [
            'name'                        => bin2hex($name),
            'addr'                        => bin2hex($abbr),
            'total_supply'                => $supply,
            'precision'                   => $precision,
            'trx_num'                     => $trx_ratio,
            'num'                         => $token_ratio,
            'start_time'                  => $start,
            'end_time'                    => $end,
            'description'                 => bin2hex($desc),
            'url'                         => bin2hex($url),
            'free_asset_net_limit'        => $limit,
            'public_free_asset_net_limit' => $public_limit,
            'frozen_supply'               => [
                'frozen_amount' => $frozen_amount,
                'frozen_days'   => $frozen_days
            ],
            'owner_address'               => Address::decode($from)
        ];
        return $this->full_node->post('/wallet/createassetissue', $payload);
    }

    function createToken()
    {
        $args = func_get_args();
        return $this->createAssetIssue(...$args);
    }

    function createAsset()
    {
        $args = func_get_args();
        return $this->createAssetIssue(...$args);
    }

    function updateAsset($url, $desc, $limit, $public_limit, $from)
    {
        $payload = [
            'url'              => bin2hex($url),
            'description'      => bin2hex($desc),
            'new_limit'        => $limit,
            'new_public_limit' => $public_limit,
            'owner_address'    => Address::decode($from)
        ];
        return $this->full_node->post('/wallet/updateasset', $payload);
    }

    function updateToken()
    {
        $args = func_get_args();
        return $this->updateAsset(...$args);
    }

    function participateAssetIssue($to, $asset, $amount, $from)
    {
        $payload = [
            'to_address'    => Address::decode($to),
            'asset_name'    => bin2hex($asset),
            'amount'        => $amount,
            'owner_address' => Address::decode($from)
        ];
        return $this->full_node->post('/wallet/participateassetissue', $payload);
    }

    function purchaseAsset()
    {
        $args = func_get_args();
        return $this->participateAssetIssue(...$args);
    }

    function purchaseToken()
    {
        $args = func_get_args();
        return $this->participateAssetIssue(...$args);
    }

    function getAssetIssueById($token, $confirmed = true)
    {
        $payload = [
            'value' => $token
        ];
        if ($confirmed) {
            return $this->solidity_node->post('/walletsolidity/getassetissuebyid', $payload);
        } else {
            return $this->full_node->post('/wallet/getassetissuebyid', $payload);
        }
    }

    function getTokenById()
    {
        $args = func_get_args();
        return $this->getAssetIssueById(...$args);
    }

    function getAssetIssueByName($token)
    {
        $payload = [
            'value' => bin2hex($token)
        ];
        return $this->full_node->post('/wallet/getassetissuebyname', $payload);
    }

    function getTokenFromId()
    {
        $args = func_get_args();
        return $this->getAssetIssueByName(...$args);
    }

    function getAssetIssueList()
    {
        return $this->full_node->get('/wallet/getassetissuelist', []);
    }

    function listTokens()
    {
        $args = func_get_args();
        return $this->getAssetIssueList(...$args);
    }

    function getAssetIssueListByName($token)
    {
        $payload = [
            'value' => bin2hex($token)
        ];
        return $this->full_node->post('/wallet/getassetissuelistbyname', $payload);
    }

    function getTokenListByName()
    {
        $args = func_get_args();
        return $this->getAssetIssueListByName(...$args);
    }

    function getAssetIssueByAccount($address)
    {
        $payload = [
            'address' => Address::decode($address)
        ];
        return $this->full_node->post('/wallet/getassetissuebyaccount', $payload);
    }

    function getTokenIssuedByAddress()
    {
        $args = func_get_args();
        return $this->getAssetIssueByAccount(...$args);
    }

    function freezeBalance($balance, $duration, $type, $from, $receiver = null)
    {
        $payload = [
            'freeze_balance'   => $balance,
            'freeze_duration'  => $duration,
            'resource'         => $type,
            'owner_address'    => Address::decode($from),
            'receiver_address' => Address::decode($receiver)
        ];
        return $this->full_node->post('/wallet/freezebalance', $payload);
    }

    function unfreezeBalance($type, $from, $receiver = null)
    {
        $payload = [
            'resource'      => $type,
            'owner_address' => Address::decode($from),
            'receiver'      => Address::decode($receiver)
        ];
        return $this->full_node->post('/wallet/unfreezebalance', $payload);
    }

    function withdrawBalance($address)
    {
        $payload = [
            'owner_address' => Address::decode($address)
        ];
        return $this->full_node->post('/wallet/withdrawbalance', $payload);
    }

    function withdrawBlockRewards()
    {
        $args = func_get_args();
        return $this->withdrawBalance(...$args);
    }

    function createWitness($address, $url)
    {
        $payload = [
            'owner_address' => Address::decode($address),
            'url'           => bin2hex($url)
        ];
        return $this->full_node->post('/wallet/createwitness', $payload);
    }

    function applyForSR()
    {
        $args = func_get_args();
        return $this->createWitness(...$args);
    }

    function getBrokerage($address, $confirmed = true)
    {
        $payload = [
            'address' => Address::decode($address)
        ];
        if ($confirmed) {
            return $this->solidity_node->post('/walletsolidity/getbrokerage', $payload);
        } else {
            return $this->full_node->post('/wallet/getbrokerage', $payload);
        }
    }

    function getUncomfirmedBrokerage($address)
    {
        return $this->getBrokerage($address, false);
    }

    function voteWitnessAccount($address, $votes)
    {
        $payload = [
            'owner_address' => Address::decode($address),
            'votes'         => $votes
        ];
        return $this->full_node->post('/wallet/votewitnessaccount', $payload);
    }

    function vote()
    {
        $args = func_get_args();
        return $this->voteWitnessAccount(...$args);
    }

    //trc20
    function deployContract($abi, $bytecode, $parameter, $name, $value, $from)
    {
        $payload = [
            'abi'                           => $abi,
            'bytecode'                      => $bytecode,
            'parameter'                     => $parameter,
            'name'                          => $name,
            'call_value'                    => $value,
            'owner_address'                 => Address::decode($from),
            'fee_limit'                     => 1000000000,
            'origin_energy_limit'           => 10000000,
            'consume_user_resource_percent' => 100
        ];
        return $this->full_node->post('/wallet/deploycontract', $payload);
    }

    function createSmartContract()
    {
        $args = func_get_args();
        return $this->deployContract(...$args);
    }

    function triggerSmartContract($contract, $function, $parameter, $value, $from)
    {
        $payload = [
            'contract_address'  => Address::decode($contract),
            'function_selector' => $function,
            'parameter'         => $parameter,
            'call_value'        => $value,
            'owner_address'     => Address::decode($from),
            'fee_limit'         => 1000000000
        ];
        return $this->full_node->post('/wallet/triggersmartcontract', $payload);
    }

    function triggerConstantSmartContract($contract, $function, $parameter, $value, $from, $confirmed = true)
    {
        $payload = [
            'contract_address'  => Address::decode($contract),
            'function_selector' => $function,
            'parameter'         => $parameter,
            'call_value'        => $value,
            'owner_address'     => Address::decode($from),
            'fee_limit'         => 1000000000
        ];
        if ($confirmed) {
            return $this->solidity_node->post('/walletsolidity/triggerconstantsmartcontract', $payload);
        } else {
            return $this->full_node->post('/wallet/triggerconstantsmartcontract', $payload);
        }
    }

    function clearAbi($contract, $from)
    {
        $payload = [
            'contract_address' => Address::decode($contract),
            'owner_address'    => Address::decode($from)
        ];
        return $this->full_node->post('/wallet/clearabi', $payload);
    }

    function updateSetting($contract, $userPercent, $from)
    {
        $payload = [
            'contract_address'              => Address::decode($contract),
            'consume_user_resource_percent' => $userPercent,
            'owner_address'                 => Address::decode($from)
        ];
        return $this->full_node->post('/wallet/updatesetting', $payload);
    }

    function updateEnergyLimit($contract, $limit, $from)
    {
        $payload = [
            'contract_address'    => Address::decode($contract),
            'origin_energy_limit' => $limit,
            'owner_address'       => Address::decode($from)
        ];
        return $this->full_node->post('/wallet/updateenergylimit', $payload);
    }

    function updateBrokerage($brokerage, $from)
    {
        $payload = [
            'brokerage'     => $brokerage,
            'owner_address' => Address::decode($from)
        ];
        return $this->full_node->post('/wallet/updatebrokerage', $payload);
    }

    function updateAccount($name, $from)
    {
        $payload = [
            'account_name'  => bin2hex($name),
            'owner_address' => Address::decode($from)
        ];
        return $this->full_node->post('/wallet/updateaccount', $payload);
    }

    function accountPermissionUpdate($ownerPermits, $witnessPermits, $activePermits, $from)
    {
        $payload = [
            'owner'         => $ownerPermits,
            'witness'       => $witnessPermits,
            'active'        => $activePermits,
            'owner_address' => Address::decode($from)
        ];
        return $this->full_node->post('/wallet/accountpermissionupdate', $payload);
    }

    function setAccountId($id, $from)
    {
        $payload = [
            'account_id'    => $id,
            'owner_address' => Address::decode($from)
        ];
        return $this->full_node->post('/wallet/setaccountid', $payload);
    }

    //dex
    function proposalCreate($parameters, $from)
    {
        $payload = [
            'parameters'    => $parameters,
            'owner_address' => Address::decode($from)
        ];
        return $this->full_node->post('/wallet/proposalcreate', $payload);
    }

    function createProposal()
    {
        $args = func_get_args();
        return $this->proposalCreate(...$args);
    }

    function listProposals()
    {
        return $this->full_node->post('/wallet/listproposals', []);
    }

    function proposalDelete($id, $from)
    {
        $payload = [
            'proposal_id'   => $id,
            'owner_address' => Address::decode($from)
        ];
        return $this->full_node->post('/wallet/proposaldelete', $payload);
    }

    function deleteProposal()
    {
        $args = func_get_args();
        return $this->proposalDelete(...$args);
    }

    function proposalApprove($id, $from)
    {
        $payload = [
            'proposal_id'   => $id,
            'owner_address' => Address::decode($from)
        ];
        return $this->full_node->post('/wallet/proposalapprove', $payload);
    }

    function voteProposal()
    {
        $args = func_get_args();
        return $this->proposalApprove(...$args);
    }

    function exchangeCreate($token1, $balance1, $token2, $balance2, $from)
    {
        $payload = [
            'first_token_id'       => bin2hex($token1),
            'first_token_balance'  => $balance1,
            'second_token_id'      => bin2hex($token2),
            'second_token_balance' => $balance2,
            'owner_address'        => Address::decode($from)
        ];
        return $this->full_node->post('/wallet/exchangecreate', $payload);
    }

    function listExchanges()
    {
        return $this->full_node->post('/wallet/listexchanges', []);
    }

    function getPaginatedExchangeList($offset = 0, $limit = 30)
    {
        $payload = [
            'offset' => $offset,
            'limit'  => $limit
        ];
        return $this->full_node->post('/wallet/getpaginatedexchangelist', $payload);
    }

    function listExchangePaginated()
    {
        $args = func_get_args();
        return $this->getPaginatedExchangeList(...$args);
    }

    function exchangeInject($exchange, $token, $quant, $from)
    {
        $payload = [
            'exchange_id'   => $exchange,
            'token_id'      => bin2hex($token),
            'quant'         => $quant,
            'owner_address' => Address::decode($from)
        ];
        return $this->full_node->post('/wallet/exchangeinject', $payload);
    }

    function injectExchangeToken()
    {
        $args = func_get_args();
        return $this->exchangeInject(...$args);
    }

    function exchangeWithdraw($exchange, $token, $quant, $from)
    {
        $payload = [
            'exchange_id'   => $exchange,
            'token_id'      => bin2hex($token),
            'quant'         => $quant,
            'owner_address' => Address::decode($from)
        ];
        return $this->full_node->post('/wallet/exchangewithdraw', $payload);
    }

    function withdrawExchangeTokens()
    {
        $args = func_get_args();
        return $this->exchangeWithdraw(...$args);
    }

    function exchangeTransaction($exchange, $token, $quant, $expected, $from)
    {
        $payload = [
            'exchange_id'   => $exchange,
            'token_id'      => bin2hex($token),
            'quant'         => $quant,
            'expected'      => $expected,
            'owner_address' => Address::decode($from)
        ];
        return $this->full_node->post('/wallet/exchangetransaction', $payload);
    }

    function getExchangeById($id)
    {
        $payload = [
            'id' => $id
        ];
        return $this->full_node->post('/wallet/getexchangebyid', $payload);
    }

    function tradeExchangeTokens()
    {
        $args = func_get_args();
        return $this->exchangeTransaction(...$args);
    }
}

