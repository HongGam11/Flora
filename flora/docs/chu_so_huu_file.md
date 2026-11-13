# Chủ sở hữu file theo nhóm sáu người

TV1 là nhóm trưởng.

- `HomeController.php`: TV2
- `AuthController.php`: TV1
- `ProfileController.php`: TV1
- `ProductController.php`: TV2
- `CartController.php`: TV4
- `CheckoutController.php`: TV5
- `OrderController.php`: TV5
- `PaymentController.php`: TV6
- `AdminDashboardController.php`: TV2
- `AdminCategoryController.php`: TV3
- `AdminProductController.php`: TV3
- `AdminVariantController.php`: TV3
- `AdminOrderController.php`: TV6
- `AdminUserController.php`: TV1
- `AdminCouponController.php`: TV4
- `AdminReportController.php`: TV2
- `UserModel.php`: TV1
- `CategoryModel.php`: TV3 phối hợp TV2
- `ProductModel.php`: TV3 phối hợp TV2
- `ProductVariantModel.php`: TV3 phối hợp TV2 TV4 TV5 TV6
- `ProductImageModel.php`: TV3 phối hợp TV2
- `CartModel.php`: TV4 phối hợp TV5
- `CouponModel.php`: TV4 phối hợp TV5
- `OrderModel.php`: TV5 phối hợp TV6 TV2
- `PaymentTransactionModel.php`: TV6
- `OrderStatusHistoryModel.php`: TV6 phối hợp TV5
- `UserEventModel.php`: TV2 phối hợp TV4
- `ReportModel.php`: TV2
- `PricingService.php`: TV4
- `CheckoutService.php`: TV5
- `OrderService.php`: TV6
- `PaymentService.php`: TV6

Layout và core TV1; CSS chung TV2; cart.js TV4; checkout.js TV5; product.js và common.js TV2.
