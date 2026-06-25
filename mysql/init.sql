SET NAMES 'utf8mb4';
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS testdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE testdb;

CREATE TABLE IF NOT EXISTS addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    street VARCHAR(255) NOT NULL,
    complement VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    neighborhood VARCHAR(100) NOT NULL,
    zip_code VARCHAR(20) NOT NULL
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    address_id INT,
    role ENUM('cliente','admin','superuser') NOT NULL DEFAULT 'cliente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (address_id) REFERENCES addresses(id)
);

INSERT IGNORE INTO addresses (street, complement, city, state, neighborhood, zip_code) VALUES (
    '123 Main St',
    'Apt 4B',
    'Anytown',
    'Anystate',
    'Downtown',
    '12345'
);

INSERT IGNORE INTO users (name, phone, email, password, address_id, role) VALUES (
    'Super User Seed',
    '0000000000',
    'superuser@example.com',
    'superpass123',
    1,
    'superuser'
);

CREATE TABLE IF NOT EXISTS suppliers(
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    address_id INT,
    FOREIGN KEY (address_id) REFERENCES addresses(id)
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100),
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    sku VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    image_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status ENUM('pendente','enviado','cancelado') NOT NULL DEFAULT 'pendente',
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_method VARCHAR(50) DEFAULT NULL,
    shipping_method VARCHAR(100) DEFAULT NULL,
    shipping_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME DEFAULT NULL,
    cancelled_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order TINYINT NOT NULL DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Endereços dos fornecedores
INSERT IGNORE INTO addresses (street, complement, city, state, neighborhood, zip_code) VALUES
    ('Av. Paulista, 1000',   'Sala 52',  'São Paulo',       'SP', 'Bela Vista',    '01310-100'),
    ('Rua das Flores, 200',  NULL,        'Curitiba',        'PR', 'Centro',        '80010-020'),
    ('Rua XV de Novembro, 300', 'Loja 3', 'Porto Alegre',   'RS', 'Centro Histórico','90020-060'),
    ('Av. Atlântica, 500',   NULL,        'Rio de Janeiro',  'RJ', 'Copacabana',    '22010-000');

-- Fornecedores
INSERT IGNORE INTO suppliers (name, phone, email, address_id) VALUES
    ('Tech Imports Ltda',    '(11) 91234-5678', 'contato@techimports.com.br',   2),
    ('Periféricos Brasil',   '(41) 93456-7890', 'vendas@perifericosbrasil.com', 3),
    ('Móveis Confort',       '(51) 94567-8901', 'comercial@moveisconfort.com',  4),
    ('Eletro Distribuidora', '(21) 95678-9012', 'pedidos@eletrodist.com.br',    5);

-- Produtos
INSERT IGNORE INTO products (supplier_id, name, description, category, price, stock, sku, status) VALUES
    (1, 'Notebook Gamer RTX 4060',
        'Notebook para jogos com placa de vídeo RTX 4060, processador Intel i7, 16GB RAM e SSD 512GB NVMe.',
        'Informática', 4599.00, 23, 'NTB-RTX4060', 'ativo'),

    (1, 'Monitor Ultrawide 34"',
        'Monitor curvo ultrawide 34 polegadas, resolução WQHD 3440x1440, 144Hz e painel IPS.',
        'Informática', 2199.90, 11, 'MON-UW34-144', 'ativo'),

    (1, 'SSD NVMe 1TB',
        'SSD M.2 NVMe com leitura de até 7.000 MB/s, compatível com PCIe 4.0.',
        'Informática', 469.90, 54, 'SSD-NVME-1TB', 'ativo'),

    (2, 'Mouse Sem Fio Ergonômico',
        'Mouse ergonômico com conexão Bluetooth e receptor USB, DPI ajustável e até 90 dias de bateria.',
        'Periféricos', 189.90, 4, 'MSE-ERGO-001', 'ativo'),

    (2, 'Teclado Mecânico TKL',
        'Teclado mecânico tenkeyless com switches Red, iluminação RGB por tecla e keycaps PBT.',
        'Periféricos', 349.00, 18, 'TEC-MEC-TKL', 'ativo'),

    (2, 'Headset 7.1 Surround',
        'Headset gamer com áudio surround 7.1 virtual, microfone retrátil com cancelamento de ruído.',
        'Periféricos', 279.00, 0, 'HDS-71-SRD', 'inativo'),

    (3, 'Cadeira Gamer Pro Max',
        'Cadeira gamer com apoio lombar ajustável, reclinável até 180° e revestimento em couro PU.',
        'Mobiliário', 1250.00, 0, 'CDR-GMPRO', 'inativo'),

    (3, 'Mesa Escritório L 160cm',
        'Mesa em L para escritório com 160cm, tampo MDF 25mm e acabamento amadeirado.',
        'Mobiliário', 890.00, 7, 'MSA-ESC-160L', 'ativo'),

    (4, 'Nobreak 1400VA',
        'Nobreak senoidal 1400VA com 8 tomadas, proteção contra surtos e autonomia de até 30 minutos.',
        'Elétricos', 749.90, 15, 'NBK-1400VA', 'ativo'),

    (4, 'Cabo HDMI 2.1 3m',
        'Cabo HDMI 2.1 com suporte a 8K 60Hz e 4K 120Hz, 3 metros, com malha trançada.',
        'Elétricos', 89.90, 102, 'CAB-HDMI21-3M', 'ativo');