<h1>API de Pagamentos</h1>

<h3>Solução de API que realiza pagamentos através da API v2 da Starkbank</h3>
<h4>Implementação:</h4>
<ul>
    <li>Laravel Framework Lumen versão 9.1.6</li>
    <li>PHP versão 8.0.19</li>
    <li>MySQL versão 8.0.29</li>
</ul>

<h4>Suporta os seguintes tipos de pagamentos:</h4>
<ul>
    <li>Transferência Bancária
</ul>

<hr>
<h3>Endpoints</h3>   
    
<table>
    <tr>
        <th>Comando REST e URI</th>
        <th>Ação</th>
        <th>Sucesso</th> 
        <th>Falha</th> 
        <th>Retorno</th> 
    </tr>
     <tr>
        <td>POST /payments/create</td>
        <td>Criar uma transação</td> 
        <td>202</td>
        <td>501</td>
        <td>JSON</td>
    </tr>
    <tr>
        <td>POST /payments/refreshTransactions</td>
        <td>Atualizar Status de uma transação</td> 
        <td>202</td>
        <td>501</td>
        <td>JSON</td>
    </tr>
</table>    
<hr>
<h2>Parâmetros</h2>

<h4>Criar uma transação</h4>
    <table>
         <tr>
            <td>POST /payments/create</td>
        </tr>
    </table>
    <h3>Request</h3>
        <table>  
            <tr>
                <th>Key</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Descição</th>
                <th>Formatos</th>
                <th>Comentário</th>
            </tr>
             <tr>
                <td>transaction</td>
                <td>Obrigatório</td> 
                <td>String</td>
                <td>Recebe o tipo da Transação que será feita</td>
                <td>"transfer", "NomeDoTipoDaTransacao", ...</td>
                <th>O código está pronto apenas para transferência bancária - transfer</th>
            </tr>
            <tr>
                <td>amount</td>
                <td>Obrigatório</td> 
                <td>Decimal</td>
                <td>Recebe o valor da Transação que será feita</td>
                <td>0.00</td>
                <th>Somente números separados com ponto (.) as casas decimais</th>
            </tr>
            <tr>
                <td>name</td>
                <td>Obrigatório</td> 
                <td>String</td>
                <td>Nome completo do Recebedor</td>
                <td>"Jéssica da Silva"</td>
                <th>Nome completo com nome e sobrenome</th>
            </tr>
                <tr>
                <td>tax_id</td>
                <td>Obrigatório</td> 
                <td>String</td>
                <td>CPF ou CNPJ do recebedor</td>
                <td>000.000.000-00 ou 00000000000<br>ou 00.000.000/0000-00 ou 00000000000000</td>
                <th>Aceita somente números ou formatado com (.) e hífen (-)</th>
            </tr>
                <tr>
                <td>tp_transfer</td>
                <td>Obrigatório</td> 
                <td>String</td>
                <td>Recebe o tipo da transferência</td>
                <td>"PIX" ou "TED"</td>
                <th></th>
            </tr>
                <tr>
                <td>bank_code</td>
                <td>Obrigatório</td> 
                <td>Integer</td>
                <td>Recebe o código do banco do recebedor.</td>
                <td>000 ou 00000000</td>
                <th>Se o tipo da transferenência for "PIX" passe o ISPB(8 digítos) do banco. Ex.:20018183 = StarkBank <br>Se for "TED" passe o código do banco ()1 a 3 digitos) Ex.: 341 = Itaú</th>
            </tr>
                <tr>
                <td>branch_code</td>
                <td>Obrigatório</td> 
                <td>String</td>
                <td>Recebe a agência do recebedor</td>
                <td>00000</td>
                <th>Recebe somente números ou se houver dígito separar com hífen (-). Ex.: 000000-0</th>
            </tr>
                <tr>
                <td>account_number</td>
                <td>Obrigatório</td> 
                <td>String</td>
                <td>Recebe a conta do recebedor</td>
                <td>00000</td>
                <th>Recebe somente números ou se houver dígito separar com hífen (-). Ex.: 000000-0</th>
            </tr>
                <tr>
                <td>account_type</td>
                <td>Opcional</td> 
                <td>String</td>
                <td>Tipo da Conta do recebedor</td>
                <td>"checking", "savings" and "salary"</td>
                <th>Por padrão é "checking", só é necessário enviar se for diferente disso.</th>
            </tr>
                <tr>
                <td>scheduled</td>
                <td>Opcional</td> 
                <td>String</td>
                <td>Recebe a data de agendamento da transferência.</td>
                <td>0000-00-00</td>
                <th>Formato americano de datas (YYY/MM/DD). Por padrão a transação será feita sempre no mesmo dia até as 16:00 (BRT), depois disso será agendada para o próximo dia útil. Sö enviar se precisar agendar para outro dia. Caso o dia enviado não for dia útil, será agendado para o próximo dia útil.</th>
            </tr>
        </table> 
         <h3>Response</h3>
