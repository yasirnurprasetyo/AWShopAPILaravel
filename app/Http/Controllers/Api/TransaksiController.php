<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Transaksi;
use App\TransaksiDetail;
use Illuminate\Support\Facades\Validator;

class TransaksiController extends Controller
{
    public function store(Request $request)
    {
        $validasi = Validator::make($request->all(), [
            'user_id' => 'required',
            'total_item' => 'required',
            'total_harga' => 'required',
            'name' => 'required',
            'phone' => 'required',
            'total_transfer' => 'required',
            'ongkir' => 'required',
            'jasa_pengiriman' => 'required',
            'bank' => 'required'
        ]);

        if($validasi->fails()){
            $val = $validasi->errors()->all();
            return $this->error($val(0));
        };

        $kodePayment = "INV/PYM/".now()->format('Y-m-d')."/".rand(100,999);
        $kodeTrx = "INV/PYM/".now()->format('Y-m-d')."/".rand(100,999);
        $kodeUnik = rand(100,999);
        $status = "MENUNGGU";
        $expiredAt = now()->addDay();

        $dataTransaksi = array_merge($request->all(), [
            'kode_payment' => $kodePayment,
            'kode_trx' => $kodeTrx,
            'kode_unik' => $kodeUnik,
            'status' => $status,
            'expired_at' => $expiredAt,
        ]);

        \DB::beginTransaction();
        $transaksi = Transaksi::create($dataTransaksi);
        foreach($request->produks as $produk){
            $detail = [
                'transaksi_id' => $transaksi->id,
                'produk_id' => $produk['id'],
                'total_item' => $produk['total_item'],
                'catatan' => $produk['catatan'],
                'total_harga' => $produk['total_harga'],
            ];
            $transaksiDetail = TransaksiDetail::create($detail);
        }

        if(!empty($transaksi) && !empty($transaksiDetail)){
            \DB::commit();
            return response()->json([
                'success' => 1,
                'message' => 'Transaksi berhasil',
                'user' => collect($transaksi)
            ]);
        }else{
            \DB::rollback();
            $this->error('Transaksi gagal');
        }
    }

    public function history($id)
    {
        $transaksis = Transaksi::with(['user'])->whereHas('user', function($query) use ($id){
            $query->whereId($id);
        })->get();

        foreach($transaksis as $transaksi){
            $details = $transaksi->details;
            foreach($details as $detail){
                $detail->produk;
            }
        }

        if(!empty($transaksis)){
            return response()->json([
                'success' => 1,
                'message' => 'Transaksi berhasil',
                'user' => collect($transaksis)
            ]);
        }else{
            $this->error('Transaksi Gagal');
        }
    }

    public function error($pesan)
    {
        return response()->json([
            'success' => 0,
            'message' => $pesan
        ]);
    }
}
