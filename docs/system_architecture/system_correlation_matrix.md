# Punap System Correlation Matrix

This document provides a system-wide correlation matrix that models the relationships between system requirements, core modules, database entities, and their interdependencies within the Punap specific architecture.

## 1. Requirements to Modules Traceability Matrix

This matrix maps the original functional requirements (from `rquiremnets.txt`) to the core system components and implementation modules.

| Req ID | Description | Core Module(s) | Key Entities / Implementation Layer |
|:---|:---|:---|:---|
| **FR-01** | User registration, login, profiles | **Authentication** | `User` Model, Custom Auth Controllers |
| **FR-02** | Role-based Access Control (RBAC) | **Authorization** | Role Middleware, `User` (isAdmin/isSuperAdmin) |
| **FR-03** | Product listing, images, CRUD | **Catalog / Products** | `Product`, `Category`, Image Upload logic |
| **FR-04** | Complete rental workflow | **Rental System** | `Rental`, `RentalRequest`, Overlap Check Logic |
| **FR-05** | Product swap, counter-offer | **Swap System** | `Swap`, `SwapRequest`, Mutual Acceptance Logic |
| **FR-06** | Khalti & eSewa payment gateways | **Payments** | `Payment`, Gateway APIs, Callback Verification |
| **FR-07** | Real-time notifications (Pusher) | **Notifications** | Pusher Broadcasting, Private Channels |
| **FR-08** | Multi-criteria keyword search & filter | **Discovery** | Query Scopes (Conditional where clauses) |
| **FR-09** | Ratings, reviews, and disputes | **Trust & Safety** | `Review`, `Dispute`, Transaction-bound Rules |

---

## 2. Component Interaction Matrix (Entity Coupling)

This matrix demonstrates how core data entities (models) interact with and correlate to each other. An intersection defines the relational logic between the Row (Source) and Column (Target).

| Entity | `User` | `Product` | `Order` | `Rental` | `Swap` | `Payment` | `Review` | `Dispute` |
|:---|:---|:---|:---|:---|:---|:---|:---|:---|
| **`User`** | - | Creates (1:M) | Places (1:M) | Requests (1:M) | Proposes (1:M) | Funds (1:M) | Writes (1:M) | Opens (1:M) |
| **`Product`** | Owned By (M:1) | - | Sold In (1:M) | Rented In (1:M) | Traded In (M:M) | - | Receives (1:M) | Flagged In (1:M) |
| **`Order`** | Bought By | Contains | - | - | - | Paid via (1:1) | Targeted in | Targeted in |
| **`Rental`** | Booked By | Applies To | - | - | - | Paid via (1:1) | Targeted in | Targeted in |
| **`Swap`** | Offered By | Applies To (x2) | - | - | - | *Owed via (1:1)* | Targeted in | Targeted in |
| **`Payment`** | Paid By | - | Pays For | Pays For | - | - | - | - |
| **`Review`** | Authored By | About | About | About | About | - | - | - |
| **`Dispute`** | Raised By | Implicates | Implicates | Implicates | Implicates | - | - | - |

*(Note: Swap payments specifically correspond to offset differences when cash is included in a swap. Products act as the anchor point for all Order/Rental/Swap actions, ensuring a listing is only subjected to valid State Machine transitions).*

---

## 3. Subsystem Interdependency Matrix

This matrix analyzes high-level architectural correlations between application subsystems, showing which domains rely on others to function natively.

| Subsystem | Depends On | Purpose of Correlation |
|:---|:---|:---|
| **Access Control** | Authentication | Validates active sessions before enforcing route roles (Admin vs User). |
| **Catalog** | File Storage, DB | Stores uploaded listing images onto the public storage disk; queries JSON type column. |
| **Transactions (All)**| Catalog (Products) | Validates product status (e.g., to prevent booking a sold/swapped item). |
| **Rentals System** | Transactions, Payments| Overlap availability check uses date boundaries. Payment finalizes rental booking. |
| **Swaps System** | Transactions | Requires an active negotiation history log spanning multiple product models. |
| **Gateways (Khalti)**| Payments | Processes financial events for Sales & Rentals; prevents callback replay attacks. |
| **Notification** | All Core Subsystems| Subscribes to Product events, Transaction updates, and Swap negotiation state changes. |
| **Moderation** | Trust & Safety | Admin system correlates heavily with Reviews, Disputes, and User reporting. |

---

## Technical Context
The Punap application utilizes conditional state flow, which means the **Product Type** (JSON configured array: `[buy, rent, swap]`) strongly correlates to how the frontend renders the detail component CTA, directly coupling the user experience state with the backend schema validation format.
