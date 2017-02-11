<?php

namespace App\Controllers;
use DHL\Entity\AM\GetQuote;
use DHL\Datatype\AM\PieceType;
use DHL\Client\Web as WebserviceClient;

/**
* This is for the quotation controller
*/
class QuotationController extends BaseController
{
	public function postIndex($params) {
		$info = $params["quote"][0];
		$xml = $this->getQuote($info);
		if(isset($xml->Response->Status) || isset($xml->Response->Note)) {
			return $this->render('404',['message'=>$xml->Response->Status->Condition->ConditionData]);
		}
		return $this->render('quotations', ['quote'=> $xml->GetQuoteResponse, 'width'=>$info['width'], 'height'=>$info['height'], 'length' => $info['length']]);
	}

	public function postBook($params) {
		return $this->render('booking', $params);
	}

	public function postMail($params) {
		$headers = [];
		$headers[] = 'MIME-Version: 1.0';
		$headers[] = 'Content-type: text/html; charset=iso-8859-1';
		$headers[] = 'From: New Shipping <tijesunimi48@gmail.com>';
		return mail('Tijesunimi Peters <tijesunimi48@gmail.com>','New Shipping', $this->render('layouts/mail', $params), implode("\r\n",$headers));
	}

	private function buildMail($params) {
	}

	private function getQuote($req) {
		$q = $this->buildQuoteXml($req);
		$client = new WebserviceClient('staging');
		$xml = new \SimpleXMLElement($client->call($q));
		return $xml;
	}

	private function buildPiece($req, $id) {
		$piece = new PieceType();
		$piece->PieceID = $id;
		$piece->Height = $req['height'];
		$piece->Depth = $req['length'];
		$piece->Width = $req['width'];
		$piece->Weight = $req['weight'];
		return $piece;
	}

	private function buildQuoteXml($req) {
		$q = new GetQuote();
		$q->SiteID = $this->config["dhl"]['id'];
		$q->Password = $this->config['dhl']['pass'];
		$q->BkgDetails->Date = date('Y-m-d');
		$q->MessageTime = strftime("%Y-%m-%dT%H:%M:%S", strtotime('now'));
		$q->MessageReference = '12345678901234567890123456789';
		$piece = $this->buildPiece($req, 1);
		$q->BkgDetails->addPiece($piece);
		$q->From->CountryCode = $req['from'];
		$q->From->City = $req['from_city'];
		$q->To->CountryCode = $req['to'];
		$q->To->City = $req['to_city'];

		$q->BkgDetails->ReadyTime = 'PT10H21M';
		$q->BkgDetails->ReadyTimeGMTOffset = date('P');
		$q->BkgDetails->DimensionUnit = $req['dim_unit'];
		$q->BkgDetails->WeightUnit = $req['weight_unit'];
		$q->BkgDetails->PaymentCountryCode = $req['payment_country_code'];
		$q->BkgDetails->NumberOfPieces = $req['no_of_pieces'];
		return $q;
	}
}