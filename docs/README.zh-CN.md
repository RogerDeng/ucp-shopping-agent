[ English ](../README.md) | [ 繁體中文 ](./README.zh-TW.md) | **[ 简体中文 ]**

# UCP 购物代理 (UCP Shopping Agent)

[![WordPress](https://img.shields.io/badge/WordPress-5.8+-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0+-purple.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

**Google Universal Commerce Protocol (UCP) 的 WooCommerce 实现** — 让 AI 代理 (AI Agents) 能够通过标准化的 REST API 来发现、浏览您的 WooCommerce 商店并进行交易。

---

## 🌟 功能特色

### 🔍 商店发现 (Store Discovery)
- 标准 `/.well-known/ucp` 发现端点
- 完整的商店能力清单 (Capability Manifest)
- 商家信息、货币、语言和时区设置

### 🛍️ 产品目录 (Product Catalog)
- 支持分页和筛选功能的产品浏览
- 通过关键字、分类、价格范围进行搜索
- 通过 ID 或 SKU 获取产品详情
- 支持可变商品 (Variable Products) 及其所有变体
- 产品图片、属性和评分信息

### 📁 分类 (Categories)
- 完整的分类层级导航
- 支持嵌套子分类
- 具备分页功能的分类产品列表

### 🛒 持久化购物车 (Persistent Cart)
- 创建和管理购物车
- 新增、更新、移除购物车项目
- 支持产品变体选择
- 自动库存验证
- 购物车过期管理

### 💳 结账 (Checkout)
- 从购物车创建结账会话 (Checkout Sessions)
- 支持直接结账 (Direct Checkout)
- 配送和账单地址管理
- 优惠券应用
- 订单确认与创建

### 📦 订单 (Orders)
- 具备筛选功能的订单列表
- 详细的订单信息
- 订单事件时间轴追踪
- 付款与配送状态

### 👤 客户管理 (Customer Management)
- 创建客户资料
- 更新账单/配送地址
- 通过 Email 搜索客户

### 🚚 配送 (Shipping)
- 实时运费计算
- 支持多配送区域 (Shipping Zones)
- 可用的配送方式列表

### ⭐ 评价 (Reviews)
- 产品评价列表
- 创建评价
- 评分分布摘要

### 🎟️ 优惠券 (Coupons)
- 发现可用优惠券
- 验证优惠券代码
- 计算折扣

### 🔔 Webhooks
- 实时订单事件通知
- HMAC-SHA256 签名验证
- **指数退避重试机制** (3 次尝试)
- 通过 WP-Cron **自动恢复失败的 Webhook**
- **在发现端点中公开签名密钥 (Signing Keys)**
- 事件：`order.created`, `order.status_changed`, `order.paid`, `order.refunded`

### 🔐 身份验证 (Authentication)
- 安全的 API 密钥验证
- 三种权限级别：`read` (读取), `write` (写入), `admin` (管理)
- 通过管理界面进行密钥管理
- 支持速率限制 (Rate Limiting)
- **API 密钥缓存**以提升性能

---

## 📋 系统需求

- WordPress 5.8 或更高版本
- WooCommerce 5.0 或更高版本
- PHP 7.4 或更高版本

---

## 🌐 外部服务 (External Services)

本插件引用或使用以下外部服务：

### 1. UCP Schema Registry
- **服务网址：** `https://ucp.dev`
- **用途：** 作为 JSON Schema 和 API 响应中的协议命名空间标识符 (Protocol Namespace Identifier)。
- **发送数据：** 无。这仅为被动参考；插件不会连接或发送数据至此服务。
- **隐私权政策：** N/A (静态文档网站)
- **服务条款：** N/A

### 2. 文档示例 (Documentation Examples)
- **服务网址：** `https://agent.example`, `https://your-store.com`
- **用途：** 仅作为文档示例和代码注释中的占位符 URL，用于演示链接关系。
- **发送数据：** 无。
- **隐私权政策：** N/A
- **服务条款：** N/A

### 3. 用户设置的 Webhooks (User-Configured Webhooks)
- **服务网址：** 因设置而异 (由用户设置)
- **用途：** 发送实时订单事件通知。
- **发送数据：** 包含订单详情、客户信息与结账状态的 JSON 负载 (Payload)。
- **发送时機：** 当特定事件发生时 (如订单创建) 立即触发，或通过 WP-Cron 进行重试。
- **隐私权政策：** 请参阅您设置作为 Webhook 接收端之特定服务的隐私权政策。

---

## 🚀 安装说明

1. 下载插件 zip 文件
2. 前往 **WordPress 后台 → 插件 → 安装插件 → 上传插件**
3. 上传 zip 文件并点击 **立即安装**
4. 点击 **启用插件**
5. 前往 **WooCommerce → UCP** 进行设置

---

## ⚙️ 设置

### 管理员设置 (Admin Settings)

请前往 WordPress 管理员面板中的 **WooCommerce → UCP**。

#### 一般设置 (General Tab)
| 设置 | 描述 | 默认值 |
|------|------|--------|
| Enable UCP | 启用/停用 UCP API 端点 | Yes |
| Rate Limit | 每个 API 密钥每分钟的最大请求数 | 100 |
| Cart Expiry | 闲置购物车过期时数 | 24 |
| Checkout Expiry | 结账会话过期分钟数 | 30 |
| Enable Logging | 启用调试用的 API 请求日志 | No |

#### API 密钥 (API Keys Tab)
- 创建带有描述的新 API 密钥
- 设置权限级别 (read/write/admin)
- 查看现有密钥与最后访问时间
- 删除未使用的密钥

#### 发现 (Discovery Tab)
- 查看您的 Discovery URL
- 快速入门指南
- 可用端点参考

---

## 🔑 身份验证 (Authentication)

### API 密钥格式
```
key_id:secret
```
示例：`ucp_abc123:saucp_secret_xyz789`

### 验证方式

**Header (推荐)**
```bash
curl -H "X-UCP-API-Key: ucp_abc123:saucp_secret_xyz789" \
  https://your-store.com/wp-json/ucp/v1/products
```

**Query Parameter (查询参数)**
```bash
curl "https://your-store.com/wp-json/ucp/v1/products?ucp_api_key=ucp_abc123:saucp_secret_xyz789"
```

### 权限级别

| 级别 | 访问权限 |
|------|----------|
| `read` | 浏览产品、分类、评价 |
| `write` | 创建购物车、结账、订单、客户 |
| `admin` | 完整权限，包含 API 密钥管理 |

---

## 📡 API 端点

### 发现 (Discovery)
| 方法 | 端点 | 验证 | 描述 |
|------|------|------|------|
| GET | `/.well-known/ucp` | 否 | 商店发现清单 |
| GET | `/wp-json/ucp/v1/discovery` | 否 | 同上 |

### 产品 (Products)
| 方法 | 端点 | 验证 | 描述 |
|------|------|------|------|
| GET | `/wp-json/ucp/v1/products` | 否 | 列出产品 |
| GET | `/wp-json/ucp/v1/products/{id}` | 否 | 依 ID 获取产品 |
| GET | `/wp-json/ucp/v1/products/search` | 否 | 搜索产品 |
| GET | `/wp-json/ucp/v1/products/sku/{sku}` | 否 | 依 SKU 获取产品 |

### 分类 (Categories)
| 方法 | 端点 | 验证 | 描述 |
|------|------|------|------|
| GET | `/wp-json/ucp/v1/categories` | 否 | 列出分类 |
| GET | `/wp-json/ucp/v1/categories/{id}` | 否 | 获取分类 |
| GET | `/wp-json/ucp/v1/categories/{id}/products` | 否 | 分类产品 |

### 购物车 (Cart)
| 方法 | 端点 | 验证 | 描述 |
|------|------|------|------|
| POST | `/wp-json/ucp/v1/carts` | Write | 创建购物车 |
| GET | `/wp-json/ucp/v1/carts/{id}` | Write | 获取购物车 |
| DELETE | `/wp-json/ucp/v1/carts/{id}` | Write | 删除购物车 |
| POST | `/wp-json/ucp/v1/carts/{id}/items` | Write | 新增项目 |
| PATCH | `/wp-json/ucp/v1/carts/{id}/items/{key}` | Write | 更新项目 |
| DELETE | `/wp-json/ucp/v1/carts/{id}/items/{key}` | Write | 移除项目 |
| POST | `/wp-json/ucp/v1/carts/{id}/checkout` | Write | 转换为结账 |

### 结账 (Checkout)
| 方法 | 端点 | 验证 | 描述 |
|------|------|------|------|
| POST | `/wp-json/ucp/v1/checkout/sessions` | Write | 创建会话 |
| GET | `/wp-json/ucp/v1/checkout/sessions/{id}` | Write | 获取会话 |
| PATCH | `/wp-json/ucp/v1/checkout/sessions/{id}` | Write | 更新会话 |
| POST | `/wp-json/ucp/v1/checkout/sessions/{id}/confirm` | Write | 确认结账 |

### 订单 (Orders)
| 方法 | 端点 | 验证 | 描述 |
|------|------|------|------|
| GET | `/wp-json/ucp/v1/orders` | Write | 列出订单 |
| GET | `/wp-json/ucp/v1/orders/{id}` | Write | 获取订单 |
| GET | `/wp-json/ucp/v1/orders/{id}/events` | Write | 订单时间轴 |

### 客户 (Customers)
| 方法 | 端点 | 验证 | 描述 |
|------|------|------|------|
| POST | `/wp-json/ucp/v1/customers` | Write | 创建客户 |
| GET | `/wp-json/ucp/v1/customers/{id}` | Write | 获取客户 |
| PATCH | `/wp-json/ucp/v1/customers/{id}` | Write | 更新客户 |
| GET | `/wp-json/ucp/v1/customers/email/{email}` | Write | 依 Email 搜索 |

### 配送 (Shipping)
| 方法 | 端点 | 验证 | 描述 |
|------|------|------|------|
| POST | `/wp-json/ucp/v1/shipping/rates` | 否 | 计算运费 |
| GET | `/wp-json/ucp/v1/shipping/methods` | 否 | 列出配送方式 |
| GET | `/wp-json/ucp/v1/shipping/zones` | 否 | 列出配送区域 |

### 评价 (Reviews)
| 方法 | 端点 | 验证 | 描述 |
|------|------|------|------|
| GET | `/wp-json/ucp/v1/reviews` | 否 | 列出评价 |
| GET | `/wp-json/ucp/v1/reviews/{id}` | 否 | 获取评价 |
| POST | `/wp-json/ucp/v1/reviews` | Write | 创建评价 |
| GET | `/wp-json/ucp/v1/reviews/product/{id}/summary` | 否 | 评分摘要 |

### 优惠券 (Coupons)
| 方法 | 端点 | 验证 | 描述 |
|------|------|------|------|
| GET | `/wp-json/ucp/v1/coupons` | 否 | 列出优惠券 |
| POST | `/wp-json/ucp/v1/coupons/validate` | 否 | 验证优惠券 |
| GET | `/wp-json/ucp/v1/coupons/code/{code}` | 否 | 依代码获取 |

### API 密钥 (API Keys)
| 方法 | 端点 | 验证 | 描述 |
|------|------|------|------|
| POST | `/wp-json/ucp/v1/auth/keys` | WP Admin | 创建密钥 |
| GET | `/wp-json/ucp/v1/auth/keys` | WP Admin | 列出密钥 |
| DELETE | `/wp-json/ucp/v1/auth/keys/{id}` | WP Admin | 删除密钥 |
| GET | `/wp-json/ucp/v1/auth/verify` | Read | 验证密钥 |

---

## 📝 使用示例

### 1. 探索商店
```bash
curl https://your-store.com/.well-known/ucp
```

### 2. 浏览产品
```bash
curl "https://your-store.com/wp-json/ucp/v1/products?per_page=10&category=15"
```

### 3. 搜索产品
```bash
curl "https://your-store.com/wp-json/ucp/v1/products/search?q=shirt&min_price=20&max_price=100"
```

### 4. 创建购物车与新增项目
```bash
# 创建购物车
curl -X POST \
  -H "X-UCP-API-Key: YOUR_API_KEY" \
  https://your-store.com/wp-json/ucp/v1/carts

# 新增项目到购物车
curl -X POST \
  -H "X-UCP-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"product_id": 123, "quantity": 2}' \
  https://your-store.com/wp-json/ucp/v1/carts/{cart_id}/items
```

### 5. 结账流程
```bash
# 将购物车转换为结账
curl -X POST \
  -H "X-UCP-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "shipping_address": {
      "first_name": "John",
      "last_name": "Doe",
      "address_1": "123 Main St",
      "city": "Beijing",
      "country": "CN"
    },
    "billing_address": {...}
  }' \
  https://your-store.com/wp-json/ucp/v1/carts/{cart_id}/checkout

# 确认结账
curl -X POST \
  -H "X-UCP-API-Key: YOUR_API_KEY" \
  https://your-store.com/wp-json/ucp/v1/checkout/sessions/{session_id}/confirm
```

---

## 🔔 Webhooks

### Webhook 功能 (v1.0.2+)

- **重试机制**：失败的 webhook 会自动重试 3 次，采用指数退避 (5s, 10s, 20s)
- **失败 Webhook 恢复**：未发送成功的 webhook 会被存储，并通过 WP-Cron 每 15 分钟重试一次
- **签名密钥**：发现端点现在会公开 `signing_keys` 以供 webhook 验证使用

### Webhook 签名验证

所有 webhook 请求都包含签名标头：

```
X-UCP-Signature: t=1705234567,v1=<hmac_signature>
X-UCP-Event: order.created
X-UCP-Timestamp: 1705234567
X-UCP-Delivery-ID: <uuid>
```

### 验证签名 (PHP 示例)
```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_UCP_SIGNATURE'];
$secret = 'your_webhook_secret';

// 解析签名：t=timestamp,v1=hash
preg_match('/t=(\d+),v1=([a-f0-9]+)/', $signature, $matches);
$timestamp = $matches[1];
$received_hash = $matches[2];

// 验证时间戳是否在 5 分钟内
if (abs(time() - $timestamp) > 300) {
    die('Signature expired');
}

// 验证签名
$message = $timestamp . '.' . $payload;
$expected_hash = hash_hmac('sha256', $message, $secret);

if (hash_equals($expected_hash, $received_hash)) {
    // Webhook 合法
    $data = json_decode($payload, true);
}
```

---

## 🗄️ 数据库数据表

本插件会创建以下自定义数据表：

| 数据表 | 用途 |
|-------|------|
| `wp_shopping_agent_ucp_api_keys` | API 密钥存储 |
| `wp_shopping_agent_ucp_cart_sessions` | 持久化购物车数据 |
| `wp_shopping_agent_ucp_checkout_sessions` | 结账会话数据 |
| `wp_shopping_agent_ucp_webhooks` | Webhook 设置 |

---

## 🌐 国际化 (Internationalization)

本插件支持翻译。翻译文件位于 `/languages` 目录中。

- Text Domain: `shopping-agent-with-ucp`
- POT 文件: `languages/shopping-agent-with-ucp.pot`

---

## 📁 文件结构

```
shopping-agent-with-ucp/
├── shopping-agent-with-ucp.php             # 主插件文件
├── admin/
│   ├── class-shopping-agent-ucp-admin.php    # 管理员功能
│   ├── class-shopping-agent-ucp-settings.php # 设置管理
│   └── views/
│       └── settings-page.php                 # 管理员 UI 模板
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
│   ├── models/                               # 数据模型
│   │   ├── class-shopping-agent-ucp-api-key.php
│   │   └── class-shopping-agent-ucp-cart-session.php
│   ├── webhooks/                             # Webhook 处理
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
// Webhook 传递失败
do_action('shopping_agent_ucp_webhook_delivery_failed', $webhook, $error);
```

### Filters
```php
// 修改 webhook SSL 验证
apply_filters('shopping_agent_ucp_webhook_ssl_verify', true);
```

---

## 🛠️ 疑难排解

### API 返回 404
- 确保您使用正确的 URL：`/wp-json/ucp/v1/...`
- 重整固定链接：**设置 → 固定链接 → 保存变更**

### 身份验证失败
- 验证 API 密钥格式：`key_id:secret`
- 检查密钥权限是否符合端点访问需求
- 确保密钥未被删除

### 购物车/结账过期
- 在 **WooCommerce → UCP → 一般** 中调整过期时间
- 默认值：购物车 = 24 小时，结账 = 30 分钟

---

## 📄 授权条款

本插件采用 GPL2 授权。详情请见 [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html)。

---

## 👨‍💻 作者

**Roger Deng**

---

## 🤝 贡献

欢迎提交贡献！请随时提交 Pull Request。

---

## 📞 支持

如需支持，请在 GitHub 仓库中建立 issue。
