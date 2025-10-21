import { createI18n } from 'vue-i18n';

// Mensagens de tradução
const messages = {
  pt: {
    metadata: {
      // Campos comuns de documento
      title: 'Título',
      description: 'Descrição',
      type: 'Tipo',
      category: 'Categoria',
      status: 'Status',
      created_at: 'Criado em',
      updated_at: 'Atualizado em',
      created_by: 'Criado por',
      updated_by: 'Atualizado por',
      author: 'Autor',
      date: 'Data',
      year: 'Ano',
      month: 'Mês',
      day: 'Dia',
      
      // Campos específicos de documentos
      document_type: 'Tipo de Documento',
      document_number: 'Número do Documento',
      document_date: 'Data do Documento',
      document_year: 'Ano do Documento',
      document_category: 'Categoria do Documento',
      document_status: 'Status do Documento',
      document_author: 'Autor do Documento',
      document_description: 'Descrição do Documento',
      
      // Campos administrativos
      file_name: 'Nome do Arquivo',
      file_path: 'Caminho do Arquivo',
      file_size: 'Tamanho do Arquivo',
      mime_type: 'Tipo MIME',
      original_name: 'Nome Original',
      
      // Campos específicos do contexto (ajuste conforme necessário)
      number: 'Número',
      department: 'Departamento',
      sector: 'Setor',
      responsible: 'Responsável',
      priority: 'Prioridade',
      deadline: 'Prazo',
      notes: 'Observações',
      comments: 'Comentários',
      reference: 'Referência',
      protocol: 'Protocolo',
      classification: 'Classificação',
      subject: 'Assunto',
      recipient: 'Destinatário',
      sender: 'Remetente',
      origin: 'Origem',
      destination: 'Destino',
      
      // Campos financeiros
      value: 'Valor',
      amount: 'Quantia',
      cost: 'Custo',
      budget: 'Orçamento',
      expense: 'Despesa',
      revenue: 'Receita',
      liquidation: 'Liquidação',
      commitment_type: 'Tipo de Empenho',
      record: 'Ficha',
      unit_number: 'Número da Unidade',
      unit: 'Unidade',
      subunit_number: 'Número da Subunidade',
      subunit: 'Subunidade',
      resource_source_number: 'Número da Fonte de Recurso',
      resource_number: 'Número da Fonte de Recurso',
      resource_source: 'Fonte de Recurso',
      supplier: 'Fornecedor',
      supplier_number: 'Número do Fornecedor',
      modality: 'Modalidade',
      modality_number: 'Número da Modalidade',
      bidding: 'Licitação',
      bidding_year: 'Ano da Licitação',
      purchasing_process: 'Processo de Compra',
      bank: 'Banco',
      account: 'Conta',
      payment_order_date: 'Data do Pedido de Pagamento',
      gross_value: 'Valor Bruto',
      discount: 'Desconto',
      net_value: 'Valor Líquido',
      net_value: 'Valor Líquido',
      expense_number: 'Número da Despesa',
      expense: 'Despesa',

      // Campos de RH
      employee: 'Funcionário',
      position: 'Cargo',
      salary: 'Salário',
      admission_date: 'Data de Admissão',
      termination_date: 'Data de Demissão',
      
      // Campos de saúde
      patient: 'Paciente',
      doctor: 'Médico',
      diagnosis: 'Diagnóstico',
      treatment: 'Tratamento',
      medication: 'Medicação',
      
      // Campos jurídicos
      law_number: 'Número da Lei',
      decree_number: 'Número do Decreto',
      ordinance_number: 'Número da Portaria',
      act_number: 'Número do Ato',
      article: 'Artigo',
      paragraph: 'Parágrafo',
      
      // Outros campos comuns
      tags: 'Tags',
      keywords: 'Palavras-chave',
      summary: 'Resumo',
      content: 'Conteúdo',
      attachments: 'Anexos',
      version: 'Versão',
      revision: 'Revisão',
      approved_by: 'Aprovado por',
      reviewed_by: 'Revisado por',
      published_date: 'Data de Publicação',
      expiry_date: 'Data de Expiração',
      validity: 'Validade',
      scope: 'Escopo',
      location: 'Localização',
      address: 'Endereço',
      contact: 'Contato',
      phone: 'Telefone',
      email: 'E-mail',
      website: 'Site',
      
      // Campos de processo
      process_number: 'Número do Processo',
      process_type: 'Tipo de Processo',
      process_status: 'Status do Processo',
      process_date: 'Data do Processo',
      process_description: 'Descrição do Processo',
    }
  }
};

// Configuração do i18n
export const i18n = createI18n({
  locale: 'pt', // idioma padrão
  fallbackLocale: 'pt', // idioma de fallback
  messages,
  legacy: false, // usar Composition API
  globalInjection: true, // permitir usar $t em templates
});

// Função helper para traduzir chaves de metadados
export function translateMetadataKey(key: string): string {
  // Normaliza a chave (lowercase, remove espaços extras)
  const normalizedKey = key.toLowerCase().trim();
  
  // Tenta encontrar a tradução
  const translation = messages.pt.metadata[normalizedKey as keyof typeof messages.pt.metadata];
  
  if (translation) {
    return translation;
  }
  
  // Se não encontrar tradução, formata a chave original
  return formatFallbackKey(key);
}

// Função para formatar chaves que não têm tradução
function formatFallbackKey(key: string): string {
  let formattedKey = key
    .replace(/_/g, ' ') // Substitui underscores por espaços
    .replace(/\bdocument\b/gi, '') // Remove a palavra 'document' (case-insensitive)
    .trim(); // Remove espaços extras no início/fim

  // Capitaliza a primeira letra de cada palavra
  formattedKey = formattedKey.replace(/\b\w/g, char => char.toUpperCase());

  return formattedKey;
}

export default i18n;
