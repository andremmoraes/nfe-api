<?php
/**
 * MIT License
 * 
 * Copyright (c) 2016 MZ Desenvolvimento de Sistemas LTDA
 * 
 * @author Francimar Alves <mazinsw@gmail.com>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */
namespace BD;
use IBPT;
use Util;
use Exception;

class Estatico extends Banco {

	private $ibpt;
	private $uf_codes;
	private $mun_codes;
	private $servicos;

	public function __construct($estatico = array()) {
		parent::__construct($estatico);
		$this->load();
	}

	public function load() {
		$json = file_get_contents(dirname(dirname(dirname(__FILE__))) . '/data/uf_ibge_code.json');
		$this->uf_codes = json_decode($json, true);
		if($this->uf_codes === false || is_null($this->uf_codes))
			throw new Exception('Falha ao carregar os códigos das unidades federadas', json_last_error());
		$json = file_get_contents(dirname(dirname(dirname(__FILE__))) . '/data/municipio_ibge_code.json');
		$this->mun_codes = json_decode($json, true);
		if($this->mun_codes === false || is_null($this->mun_codes))
			throw new Exception('Falha ao carregar os códigos dos municípios', json_last_error());
		$json = file_get_contents(dirname(dirname(dirname(__FILE__))) . '/data/servicos.json');
		$this->servicos = json_decode($json, true);
		if($this->servicos === false || is_null($this->servicos))
			throw new Exception('Falha ao carregar serviços da SEFAZ', json_last_error());
	}

	public function getIBPT() {
		return $this->ibpt;
	}

	public function setIBPT($ibpt) {
		$this->ibpt = $ibpt;
		return $this;
	}

	/**
	 * Obtém o código IBGE do estado
	 */
	public function getCodigoEstado($uf) {
		$codigo = $this->uf_codes['estados'][strtoupper($uf)];
		if(is_null($codigo))
			throw new Exception('Não foi encontrado o código do IBGE para o estado "'.$uf.'"', 404);
		return intval($codigo);
	}

	/**
	 * Obtém a aliquota do imposto de acordo com o tipo
	 */
	public function getImpostoAliquota($ncm, $uf, $ex = null, $cnpj = null, $token = null) {
		return $this->getIBPT()->getImposto($cnpj, $token, $ncm, $uf, $ex);
	}

	/**
	 * Obtém o código IBGE do município
	 */
	public function getCodigoMunicipio($municipio, $uf) {
		$array = $this->mun_codes['municipios'][strtoupper($uf)];
		$elem = array('nome' => $municipio);
		$o = Util::binarySearch($elem, $array, function($o1, $o2) {
			$n1 = Util::removeAccent($o1['nome']);
			$n2 = Util::removeAccent($o2['nome']);
			return strcasecmp($n1, $n2);
		});
		if($o === false)
			throw new Exception('Não foi encontrado o código do IBGE para o município "'.$municipio.'" do estado "'.$uf.'"', 404);
		return $o['codigo'];
	}

	/**
	 * Obtém as notas pendentes de envio
	 */
	public function getNotasPendentes($inicio = null, $quantidade = null) {
		return array(); // TODO implementar
	}

	public function getInformacaoServico($emissao, $uf, $modelo = null, $ambiente = null) {
		switch ($emissao) {
			case '1':
				$emissao = 'normal';
				break;
			case '9':
				$emissao = 'contingencia';
				break;
		}
		switch ($modelo) {
			case '55':
				$modelo = 'nfe';
				break;
			case '65':
				$modelo = 'nfce';
				break;
		}
		if($modelo == 'nfce')
			$emissao = 'normal'; // NFCe envia contingência pelo webservice normal
		$array = $this->servicos[$emissao];
		if(is_null($array))
			throw new Exception('Falha ao obter o serviço da SEFAZ para o tipo de emissão "'.$emissao.'"', 404);
		$array = $array[strtoupper($uf)];
		if(is_null($array))
			throw new Exception('Falha ao obter o serviço da SEFAZ para a UF "'.$uf.'"', 404);
		if(!is_array($array))
			$array = $this->getInformacaoServico($emissao, $array);
		$_modelos = array('nfe', 'nfce');
		foreach ($_modelos as $_modelo) {
			if(!isset($array[$_modelo]))
				continue;
			$node = $array[$_modelo];
			if(!is_array($node))
				$node = $this->getInformacaoServico($emissao, $node, $_modelo);
			if(isset($node['base'])) {
				$base = $this->getInformacaoServico($emissao, $node['base'], $_modelo);
				$node = array_replace_recursive($node, $base);
			}
			$array[$_modelo] = $node;
		}
		if(!is_null($modelo)) {
			$array = $array[$modelo];
			if(is_null($array))
				throw new Exception('Falha ao obter o serviço da SEFAZ para o modelo de nota "'.$modelo.'"', 404);
		}
		switch ($ambiente) {
			case '1':
				$ambiente = 'producao';
				break;
			case '2':
				$ambiente = 'homologacao';
				break;
		}
		if(!is_null($modelo) && !is_null($ambiente)) {
			$array = $array[$ambiente];
			if(is_null($array))
				throw new Exception('Falha ao obter o serviço da SEFAZ para o ambiente "'.$ambiente.'"', 404);
		}
		return $array;
	}

	public function toArray() {
		$estatico = parent::toArray();
		$estatico['ibpt'] = $this->getIBPT();
		return $estatico;
	}

	public function fromArray($estatico = array()) {
		if($estatico instanceof Estatico)
			$estatico = $estatico->toArray();
		else if(!is_array($estatico))
			return $this;
		parent::fromArray($estatico);
		$this->setIBPT($estatico['ibpt']);
		if(is_null($this->getIBPT()))
			$this->setIBPT(new IBPT());
		return $this;
	}

}