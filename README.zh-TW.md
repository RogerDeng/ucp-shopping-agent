[ 🇺🇸 English ](./README.md) | **[ 🇹🇼 繁體中文 ]**

# UCP Shopping Agent (UCP 購物代理)

[![WordPress](https://img.shields.io/badge/WordPress-5.8+-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0+-purple.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

**Google Universal Commerce Protocol (UCP) 的 WooCommerce 實作** — 讓 AI 代理 (AI Agents) 能夠透過標準化的 REST API 來探索、瀏覽您的 WooCommerce 商店並進行交易。

---

## 🌟 功能特色

### 🔍 商店探索 (Store Discovery)
- 標準 `/.well-known/ucp` 探索端點
- 完整的商店能力清單 (Capability Manifest)
- 商家資訊、貨幣、語系和時區設定

### 🛍️ 產品目錄 (Product Catalog)
- 支援分頁和篩選功能的產品瀏覽
- 透過關鍵字、分類、價格範圍進行搜尋
- 透過 ID 或 SKU 取得產品詳情
- 支援可變商品 (Variable Products) 及其所有變體
- 產品圖片、屬性和評分資訊

### 📁 分類 (Categories)
- 完整的分類階層導覽
- 支援巢狀子分類
- 具備分頁功能的分類產品列表

### 🛒 持久化購物車 (Persistent Cart)
- 建立和管理購物車
- 新增、更新、移除購物車項目
- 支援產品變體選擇
- 自動庫存驗證
- 購物車過期管理

### 💳 結帳 (Checkout)
- 從購物車建立結帳工作階段 (Checkout Sessions)
- 支援直接結帳 (Direct Checkout)
- 運送和帳單地址管理
- 優惠券應用
- 訂單確認與建立

### 📦 訂單 (Orders)
- 具備篩選功能的訂單列表
- 詳細的訂單資訊
- 訂單事件時間軸追蹤
- 付款與運送狀態

### 👤 客戶管理 (Customer Management)
- 建立客戶資料
- 更新帳單/運送地址
- 透過 Email 搜尋客戶

### 🚚 運送 (Shipping)
- 即時運費計算
- 支援多運送區域 (Shipping Zones)
- 可用的運送方式列表

### ⭐ 評價 (Reviews)
- 產品評價列表
- 建立評價
- 評分分佈摘要

### 🎟️ 優惠券 (Coupons)
- 探索可用優惠券
- 驗證優惠券代碼
- 計算折扣

### 🔔 Webhooks
- 即時訂單事件通知
- HMAC-SHA256 簽章驗證
- **指數退避重試機制** (3 次嘗試)
- 透過 WP-Cron **自動復原失敗的 Webhook**
- **在探索端點中公開簽署金鑰 (Signing Keys)**
- 事件：`order.created`, `order.status_changed`, `order.paid`, `order.refunded`

### 🔐 身份驗證 (Authentication)
- 安全的 API 金鑰驗證
- 三種權限級別：`read` (讀取), `write` (寫入), `admin` (管理)
- 透過管理介面進行金鑰管理
- 支援速率限制 (Rate Limiting)
- **API 金鑰快取**以提升效能

---

## 📋 系統需求

- WordPress 5.8 或更高版本
- WooCommerce 5.0 或更高版本
- PHP 7.4 或更高版本

---

## 🌐 外部服務 (External Services)

本外掛引用或使用以下外部服務：

### 1. UCP Schema Registry
- **服務網址：** `https://ucp.dev`
- **用途：** 作為 JSON Schema 和 API 回應中的協議命名空間識別符 (Protocol Namespace Identifier)。
- **傳送資料：** 無。這僅為被動參考；外掛不會連接或傳送資料至此服務。
- **隱私權政策：** N/A (靜態文件網站)
- **服務條款：** N/A

### 2. 文件範例 (Documentation Examples)
- **服務網址：** `https://agent.example`, `https://your-store.com`
- **用途：** 僅作為文件範例和程式碼註釋中的佔位符 URL，用於演示連結關係。
- **傳送資料：** 無。
- **隱私權政策：** N/A
- **服務條款：** N/A

### 3. 使用者設定的 Webhooks (User-Configured Webhooks)
- **服務網址：** 因設定而異 (由使用者設定)
- **用途：** 發送即時訂單事件通知。
- **傳送資料：** 包含訂單詳情、客戶資訊與結帳狀態的 JSON 負載 (Payload)。
- **傳送時機：** 當特定事件發生時 (如訂單建立) 立即觸發，或透過 WP-Cron 進行重試。
- **隱私權政策：** 請參閱您設定作為 Webhook 接收端之特定服務的隱私權政策。

---

## 🚀 安裝說明

1. 下載外掛 zip 檔案
2. 前往 **WordPress 後台 → 外掛 → 安裝外掛 → 上傳外掛**
3. 上傳 zip 檔案並點擊 **立即安裝**
4. 點擊 **啟用外掛**
5. 前往 **WooCommerce → UCP** 進行設定

---

## ⚙️ 設定

### 管理員設定 (Admin Settings)

請前往 WordPress 管理員面板中的 **WooCommerce → UCP**。

#### 一般設定 (General Tab)
| 設定 | 描述 | 預設值 |
|------|------|--------|
| Enable UCP | 啟用/停用 UCP API 端點 | Yes |
| Rate Limit | 每個 API 金鑰每分鐘的最大請求數 | 100 |
| Cart Expiry | 閒置購物車過期時數 | 24 |
| Checkout Expiry | 結帳工作階段過期分鐘數 | 30 |
| Enable Logging | 啟用除錯用的 API 請求日誌 | No |

#### API 金鑰 (API Keys Tab)
- 建立帶有描述的新 API 金鑰
- 設定權限級別 (read/write/admin)
- 查看現有金鑰與最後存取時間
- 刪除未使用的金鑰

#### 探索 (Discovery Tab)
- 查看您的 Discovery URL
- 快速入門指南
- 可用端點參考

---

## 🔑 身份驗證 (Authentication)

### API 金鑰格式
```
key_id:secret
```
範例：`ucp_abc123:saucp_secret_xyz789`

### 驗證方式

**Header (推薦)**
```bash
curl -H "X-UCP-API-Key: ucp_abc123:saucp_secret_xyz789" \
  https://your-store.com/wp-json/ucp/v1/products
```

**Query Parameter (查詢參數)**
```bash
curl "https://your-store.com/wp-json/ucp/v1/products?ucp_api_key=ucp_abc123:saucp_secret_xyz789"
```

### 權限級別

| 級別 | 存取權限 |
|------|----------|
| `read` | 瀏覽產品、分類、評價 |
| `write` | 建立購物車、結帳、訂單、客戶 |
| `admin` | 完整權限，包含 API 金鑰管理 |

---

## 📡 API 端點

### 探索 (Discovery)
| 方法 | 端點 | 驗證 | 描述 |
|------|------|------|------|
| GET | `/.well-known/ucp` | 否 | 商店探索清單 |
| GET | `/wp-json/ucp/v1/discovery` | 否 | 同上 |

### 產品 (Products)
| 方法 | 端點 | 驗證 | 描述 |
|------|------|------|------|
| GET | `/wp-json/ucp/v1/products` | 否 | 列出產品 |
| GET | `/wp-json/ucp/v1/products/{id}` | 否 | 依 ID 取得產品 |
| GET | `/wp-json/ucp/v1/products/search` | 否 | 搜尋產品 |
| GET | `/wp-json/ucp/v1/products/sku/{sku}` | 否 | 依 SKU 取得產品 |

### 分類 (Categories)
| 方法 | 端點 | 驗證 | 描述 |
|------|------|------|------|
| GET | `/wp-json/ucp/v1/categories` | 否 | 列出分類 |
| GET | `/wp-json/ucp/v1/categories/{id}` | 否 | 取得分類 |
| GET | `/wp-json/ucp/v1/categories/{id}/products` | 否 | 分類產品 |

### 購物車 (Cart)
| 方法 | 端點 | 驗證 | 描述 |
|------|------|------|------|
| POST | `/wp-json/ucp/v1/carts` | Write | 建立購物車 |
| GET | `/wp-json/ucp/v1/carts/{id}` | Write | 取得購物車 |
| DELETE | `/wp-json/ucp/v1/carts/{id}` | Write | 刪除購物車 |
| POST | `/wp-json/ucp/v1/carts/{id}/items` | Write | 新增項目 |
| PATCH | `/wp-json/ucp/v1/carts/{id}/items/{key}` | Write | 更新項目 |
| DELETE | `/wp-json/ucp/v1/carts/{id}/items/{key}` | Write | 移除項目 |
| POST | `/wp-json/ucp/v1/carts/{id}/checkout` | Write | 轉換為結帳 |

### 結帳 (Checkout)
| 方法 | 端點 | 驗證 | 描述 |
|------|------|------|------|
| POST | `/wp-json/ucp/v1/checkout/sessions` | Write | 建立工作階段 |
| GET | `/wp-json/ucp/v1/checkout/sessions/{id}` | Write | 取得工作階段 |
| PATCH | `/wp-json/ucp/v1/checkout/sessions/{id}` | Write | 更新工作階段 |
| POST | `/wp-json/ucp/v1/checkout/sessions/{id}/confirm` | Write | 確認結帳 |

### 訂單 (Orders)
| 方法 | 端點 | 驗證 | 描述 |
|------|------|------|------|
| GET | `/wp-json/ucp/v1/orders` | Write | 列出訂單 |
| GET | `/wp-json/ucp/v1/orders/{id}` | Write | 取得訂單 |
| GET | `/wp-json/ucp/v1/orders/{id}/events` | Write | 訂單時間軸 |

### 客戶 (Customers)
| 方法 | 端點 | 驗證 | 描述 |
|------|------|------|------|
| POST | `/wp-json/ucp/v1/customers` | Write | 建立客戶 |
| GET | `/wp-json/ucp/v1/customers/{id}` | Write | 取得客戶 |
| PATCH | `/wp-json/ucp/v1/customers/{id}` | Write | 更新客戶 |
| GET | `/wp-json/ucp/v1/customers/email/{email}` | Write | 依 Email 搜尋 |

### 運送 (Shipping)
| 方法 | 端點 | 驗證 | 描述 |
|------|------|------|------|
| POST | `/wp-json/ucp/v1/shipping/rates` | 否 | 計算運費 |
| GET | `/wp-json/ucp/v1/shipping/methods` | 否 | 列出運送方式 |
| GET | `/wp-json/ucp/v1/shipping/zones` | 否 | 列出運送區域 |

### 評價 (Reviews)
| 方法 | 端點 | 驗證 | 描述 |
|------|------|------|------|
| GET | `/wp-json/ucp/v1/reviews` | 否 | 列出評價 |
| GET | `/wp-json/ucp/v1/reviews/{id}` | 否 | 取得評價 |
| POST | `/wp-json/ucp/v1/reviews` | Write | 建立評價 |
| GET | `/wp-json/ucp/v1/reviews/product/{id}/summary` | 否 | 評分摘要 |

### 優惠券 (Coupons)
| 方法 | 端點 | 驗證 | 描述 |
|------|------|------|------|
| GET | `/wp-json/ucp/v1/coupons` | 否 | 列出優惠券 |
| POST | `/wp-json/ucp/v1/coupons/validate` | 否 | 驗證優惠券 |
| GET | `/wp-json/ucp/v1/coupons/code/{code}` | 否 | 依代碼取得 |

### API 金鑰 (API Keys)
| 方法 | 端點 | 驗證 | 描述 |
|------|------|------|------|
| POST | `/wp-json/ucp/v1/auth/keys` | WP Admin | 建立金鑰 |
| GET | `/wp-json/ucp/v1/auth/keys` | WP Admin | 列出金鑰 |
| DELETE | `/wp-json/ucp/v1/auth/keys/{id}` | WP Admin | 刪除金鑰 |
| GET | `/wp-json/ucp/v1/auth/verify` | Read | 驗證金鑰 |

---

## 📝 使用範例

### 1. 探索商店
```bash
curl https://your-store.com/.well-known/ucp
```

### 2. 瀏覽產品
```bash
curl "https://your-store.com/wp-json/ucp/v1/products?per_page=10&category=15"
```

### 3. 搜尋產品
```bash
curl "https://your-store.com/wp-json/ucp/v1/products/search?q=shirt&min_price=20&max_price=100"
```

### 4. 建立購物車與新增項目
```bash
# 建立購物車
curl -X POST \
  -H "X-UCP-API-Key: YOUR_API_KEY" \
  https://your-store.com/wp-json/ucp/v1/carts

# 新增項目到購物車
curl -X POST \
  -H "X-UCP-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"product_id": 123, "quantity": 2}' \
  https://your-store.com/wp-json/ucp/v1/carts/{cart_id}/items
```

### 5. 結帳流程
```bash
# 將購物車轉換為結帳
curl -X POST \
  -H "X-UCP-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "shipping_address": {
      "first_name": "John",
      "last_name": "Doe",
      "address_1": "123 Main St",
      "city": "Taipei",
      "country": "TW"
    },
    "billing_address": {...}
  }' \
  https://your-store.com/wp-json/ucp/v1/carts/{cart_id}/checkout

# 確認結帳
curl -X POST \
  -H "X-UCP-API-Key: YOUR_API_KEY" \
  https://your-store.com/wp-json/ucp/v1/checkout/sessions/{session_id}/confirm
```

---

## 🔔 Webhooks

### Webhook 功能 (v1.0.2+)

- **重試機制**：失敗的 webhook 會自動重試 3 次，採用指數退避 (5s, 10s, 20s)
- **失敗 Webhook 復原**：未發送成功的 webhook 會被儲存，並透過 WP-Cron 每 15 分鐘重試一次
- **簽署金鑰**：探索端點現在會公開 `signing_keys` 以供 webhook 驗證使用

### Webhook 簽章驗證

所有 webhook 請求都包含簽章標頭：

```
X-UCP-Signature: t=1705234567,v1=<hmac_signature>
X-UCP-Event: order.created
X-UCP-Timestamp: 1705234567
X-UCP-Delivery-ID: <uuid>
```

### 驗證簽章 (PHP 範例)
```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_UCP_SIGNATURE'];
$secret = 'your_webhook_secret';

// 解析簽章：t=timestamp,v1=hash
preg_match('/t=(\d+),v1=([a-f0-9]+)/', $signature, $matches);
$timestamp = $matches[1];
$received_hash = $matches[2];

// 驗證時間戳記是否在 5 分鐘內
if (abs(time() - $timestamp) > 300) {
    die('Signature expired');
}

// 驗證簽章
$message = $timestamp . '.' . $payload;
$expected_hash = hash_hmac('sha256', $message, $secret);

if (hash_equals($expected_hash, $received_hash)) {
    // Webhook 合法
    $data = json_decode($payload, true);
}
```

---

## 🗄️ 資料庫資料表

本外掛會建立以下自定義資料表：

| 資料表 | 用途 |
|-------|------|
| `wp_shopping_agent_ucp_api_keys` | API 金鑰儲存 |
| `wp_shopping_agent_ucp_cart_sessions` | 持久化購物車資料 |
| `wp_shopping_agent_ucp_checkout_sessions` | 結帳工作階段資料 |
| `wp_shopping_agent_ucp_webhooks` | Webhook 設定 |

---

## 🌐 國際化 (Internationalization)

本外掛支援翻譯。翻譯檔案位於 `/languages` 目錄中。

- Text Domain: `shopping-agent-with-ucp`
- POT 檔案: `languages/shopping-agent-with-ucp.pot`

---

## 📁 檔案結構

```
shopping-agent-with-ucp/
├── shopping-agent-with-ucp.php             # 主外掛檔案
├── admin/
│   ├── class-shopping-agent-ucp-admin.php    # 管理員功能
│   ├── class-shopping-agent-ucp-settings.php # 設定管理
│   └── views/
│       └── settings-page.php                 # 管理員 UI 模板
├── includes/
│   ├── api/                                  # REST API 控制器
│   │   ├── class-shopping-agent-ucp-rest-controller.php
│   │   ├── class-shopping-agent-ucp-auth.php
│   │   ├── class-shopping-agent-ucp-discovery.php
│   │   ├── class-shopping-agent-ucp-products.php
│   │   ├── class-shopping-agent-ucp-categories.php
│   │   ├── class-shopping-agent-ucp-cart.php
│   │   ├── class-shopping-agent-ucp-checkout.php
│   │   ├── class-shopping-agent-ucp-orders.php
│   │   ├── class-shopping-agent-ucp-customers.php
│   │   ├── class-shopping-agent-ucp-shipping.php
│   │   ├── class-shopping-agent-ucp-reviews.php
│   │   └── class-shopping-agent-ucp-coupons.php
│   ├── models/                               # 資料模型
│   │   ├── class-shopping-agent-ucp-api-key.php
│   │   └── class-shopping-agent-ucp-cart-session.php
│   ├── webhooks/                             # Webhook 處理
│   │   ├── class-shopping-agent-ucp-webhook-manager.php
│   │   └── class-shopping-agent-ucp-webhook-sender.php
│   ├── class-shopping-agent-ucp-activator.php
│   ├── class-shopping-agent-ucp-deactivator.php
│   ├── class-shopping-agent-ucp-loader.php
│   └── class-shopping-agent-ucp-i18n.php
├── assets/
│   ├── css/admin.css
│   └── js/admin.js
└── languages/
    └── wc-ucp-agent.pot
```

---

## 🔧 Hooks & Filters

### Actions
```php
// Webhook 傳遞失敗
do_action('shopping_agent_ucp_webhook_delivery_failed', $webhook, $error);
```

### Filters
```php
// 修改 webhook SSL 驗證
apply_filters('shopping_agent_ucp_webhook_ssl_verify', true);
```

---

## 🛠️ 疑難排解

### API 回傳 404
- 確保您使用正確的 URL：`/wp-json/ucp/v1/...`
- 重整永久連結：**設定 → 永久連結 → 儲存變更**

### 身份驗證失敗
- 驗證 API 金鑰格式：`key_id:secret`
- 檢查金鑰權限是否符合端點存取需求
- 確保金鑰未被刪除

### 購物車/結帳過期
- 在 **WooCommerce → UCP → 一般** 中調整過期時間
- 預設值：購物車 = 24 小時，結帳 = 30 分鐘

---

## 📄 授權條款

本外掛採用 GPL2 授權。詳情請見 [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html)。

---

## 👨‍💻 作者

**Roger Deng**

---

## 🤝 貢獻

歡迎提交貢獻！請隨時提交 Pull Request。

---

## 📞 支援

如需支援，請在 GitHub 儲存庫中建立 issue。
