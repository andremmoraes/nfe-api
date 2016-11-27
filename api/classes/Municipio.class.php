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

class Municipio {

	private $codigo;
	private $nome;

	public function __construct($municipio = array()) {
		$this->fromArray($municipio);
	}

	/**
	 * Código do município (utilizar a tabela do IBGE), informar 9999999 para
	 * operações com o exterior.
	 */
	public function getCodigo($normalize = false) {
		if(!$normalize)
			return $this->codigo;
		return $this->codigo;
	}

	public function setCodigo($codigo) {
		$this->codigo = $codigo;
		return $this;
	}

	/**
	 * Nome do munícipio
	 */
	public function getNome($normalize = false) {
		if(!$normalize)
			return $this->nome;
		return $this->nome;
	}

	public function setNome($nome) {
		$this->nome = $nome;
		return $this;
	}

	public function toArray() {
		$municipio = array();
		$municipio['codigo'] = $this->getCodigo();
		$municipio['nome'] = $this->getNome();
		return $municipio;
	}

	public function fromArray($municipio = array()) {
		if($municipio instanceof Municipio)
			$municipio = $municipio->toArray();
		else if(!is_array($municipio))
			return $this;
		$this->setCodigo($municipio['codigo']);
		$this->setNome($municipio['nome']);
		return $this;
	}

}