# Registo de alterações do plugin import2calendar

>**IMPORTANTE**
Se não houver informações sobre a atualização, isso significa que esta diz respeito apenas à atualização da documentação, da tradução ou do texto.

# 27/09/2026 Beta 1.5.0
- Início e fim dos eventos personalizados ajustáveis ao minuto (0 a 360 min)
- A atualização das agendas consome muito menos recursos, sobretudo no caso de agendas volumosas
- Uma agenda com erros já não impede a atualização das agendas seguintes
- Mensagens de erro mais precisas nos registos
- Correção de notas e locais perdidos quando contêm uma vírgula ou um ponto e vírgula
- Correção das recorrências mensais do tipo «2.ª segunda-feira» ou «última sexta-feira», que não constavam nos comandos para os próximos dias
- Correção da interrupção da importação em determinados eventos recorrentes
- Correção de eventos antigos sem data de término que foram mantidos por engano
- Correção de fusos horários mal reconhecidos (Austrália, América do Sul, México…) que bloqueavam a importação ou desfasavam os horários
- Um fuso horário desconhecido já não interrompe a importação: em vez disso, é utilizado o fuso horário do Jeedom
- Correção de um calendário criado em duplicado a cada atualização no Jeedom em italiano; uma mensagem indica as entradas duplicadas a eliminar

# 25/02/2026 Beta 1.4.8
- Correção de um erro na verificação da data

# 05/02/2026 Beta 1.4.7
- Autorização para alterar o nome da agenda

# 04/10/2025 Versão estável 1.4.2
- Correção de um erro relacionado com aspas duplas no nome do evento

# 16/06/2025 beta 1.4.1
- Correção de um erro no Cron

# 23/05/2025 Versão estável 1.4.0
- Versão mínima do Jeedom: 4.4
- Versão mínima do Debian: 11

# 07/05/2025 Beta 1.3.6
- Transferência do ficheiro ical antes do processamento

# 25/04/2025 Beta 1.3.5
- Adicionar um tempo limite para as solicitações
- Adicionar uma tentativa de repetição para as solicitações
- Correção do calendário do Booking

# 09/04/2025 Beta 1.3.4
- Pequenas correções

# 05/04/2025 Beta 1.3.3
- Correção de um erro no ical2calendar relativo aos eventos de j-1 a j+7

# 06/03/2025 Beta 1.2.8
- Correção de um bug no ical2calendar
- Adicionados os comandos j-1, bem como j+2 a j+7
- Tratar as ocorrências que se repetem a cada dia e não a cada data

# 21/02/2025 Beta 1.2.7
- Correção do fuso horário
- Adicionar comando «Atualizar»

# 21/02/2025 Beta 1.2.6
- Correção das datas de exclusão e inclusão
- Correção do cmd hoje, se houver evento a cada duas semanas

# 11/02/2025 Beta 1.2.5
- Criação dos comandos «hoje» e «amanhã» no plugin Agenda para os eventos do seu iCal.
- Correção das datas do dia
- Outras pequenas correções

# 03/02/2025 Beta e Estável 1.2.0
- Correção caso o fuso horário esteja mal formatado

# 30/01/2025 Beta 1.1.9
- Correção relativa a um evento recorrente semanal proveniente de um calendário Infomaniak

# 24/01/2025 Versão estável 1.1.8
- Correção de **outros** nas ações

# 09/01/2025 Beta 1.1.7
- Correção da atualização do calendário com datas excluídas

# 07/01/2024 Beta 1.1.6
- Correção da hora de fim do dia inteiro

# 06/11/2024 Versão estável 1.1.5
- Adicionar férias escolares dos DOM e TOM

# 31/10/2024 Versão estável 1.1.4
- Correção de frequência (ver documentação)

# 21/10/2024 Versão estável 1.1.3
- Correção: se o evento incluir um alarme, o título e a descrição eram alterados.
- Correção da importação de links webcal.

# 16/10/2024 Versão estável 1.1.2
- Correção na recuperação de eventos ao longo de vários anos

# 07/10/2024 Versão estável 1.1.1
- Correção caso exista uma vírgula no nome do evento

# 03/10/2024 Versão estável 1.1.0
- Correção relativa à eliminação ou deslocamento de um evento presente numa recorrência

# 01/10/2024 Beta 1.0.9
- Correções de avisos PHP
- Adicionar o número da versão do plugin
- Correção na atualização do evento

# 17/08/2024 Versão estável 1.0.8
- Tradução para inglês, alemão, espanhol, italiano e português. Obrigado, @mips

# 06/05/2024 Versão estável 1.0.7
- Corrigir o erro setTime.

# 06/05/2024 Beta 1.0.6
- Adicionada a possibilidade de definir manualmente a hora de início e de fim de um evento.

# 01/05/2024 Beta 1.0.5
- Consideração das datas excluídas nas recorrências
- Consideração das datas alteradas nas recorrências

# 29/04/2024 Versão estável 1.0.0
- Conversão de emojis para HTML (visível no nome do evento e na descrição (JeeMate v3))
- Conversão de fusos horários para o formato do Windows (estilo «Romance Standard Time»)
- Adicionar opções para as ações (todas, outras, evento)
- Consideração da localização (visível no JeeMate v3)
- Possibilidade de configurar um início e um fim diferentes.

# 25/04/2024 Beta 0.8.0
- Remoção dos emojis do nome do evento (erro MySQL 22007)

# 25/04/2024 Versão estável 0.7.0
- Experimenta consultar a documentação do Jeedom

# 01/04/2024 Beta 0.6.0
- Adicionado botão «documentação» e «registo de alterações»
- Adicionar botão para o Discord (no Discord JeeMate, 1 sala dedicada)

# 30/03/2024 Versão estável 0.5.0
- primeira versão estável

# 29/03/2024 Beta 0.5.0
- correção da cor do texto no campo de entrada em modo escuro + pequena alteração visual
- cor personalizada, não distinguir entre maiúsculas e minúsculas
- eventos com ocorrências: se o último evento da série tiver mais de 3 dias, a série não é apresentada.

# 28/03/2024 Beta 0.4.0
- Correção do fuso horário
- Inclusão das descrições (visíveis no JeeMate)
- Gestão de tarefas recorrentes
- Gestão do fim da recorrência, por data ou por número de repetições
- Gestão específica das cores para determinados eventos

# 12/03/2024 Beta 0.3.0
- Correção para compatibilidade com o Jeedom 4.3
- Cor do texto e do fundo definidas por predefinição

# 11/03/2024 Beta 0.1.0
- primeira versão Beta
