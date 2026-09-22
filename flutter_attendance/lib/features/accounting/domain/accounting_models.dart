/// Models for the cashier accounting module — mirrors the web
/// Accounting controller's data shapes (paymentsaccounts, fees,
/// payment_audit_log, studeaccount aggregates).
library;

/// Cashier dashboard numbers — same as Page::accounting on the web.
class AccountingDashboard {
  const AccountingDashboard({
    required this.sem,
    required this.sy,
    required this.accountsBalance,
    required this.collectionToday,
    required this.collectionMonth,
    required this.collectionYear,
    required this.trend,
    required this.recentPayments,
  });

  final String sem;
  final String sy;
  final int accountsBalance;
  final double collectionToday;
  final double collectionMonth;
  final double collectionYear;

  /// [{date: 'YYYY-MM-DD', total: n}] — 14-day collection trend.
  final List<({String date, double total})> trend;
  final List<PaymentEntry> recentPayments;

  factory AccountingDashboard.fromJson(Map<String, dynamic> j) {
    double money(dynamic v) => (v is num) ? v.toDouble() : double.tryParse('$v') ?? 0;
    return AccountingDashboard(
      sem: (j['sem'] ?? '').toString(),
      sy: (j['sy'] ?? '').toString(),
      accountsBalance:
          (j['accounts_balance'] as num?)?.toInt() ?? _countOf(j['accounts_balance']),
      collectionToday: money(j['collection_today']),
      collectionMonth: money(j['collection_month']),
      collectionYear: money(j['collection_year']),
      trend: ((j['trend'] as List?) ?? [])
          .map((e) => (
                date: (e['date'] ?? e['PDate'] ?? '').toString(),
                total: money(e['total'] ?? e['amount'] ?? 0),
              ))
          .toList(),
      recentPayments: ((j['recent_payments'] as List?) ?? [])
          .map((e) => PaymentEntry.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }

  /// totalStudeAccountProfile may return a count or a row depending on the
  /// model — accept both shapes.
  static int _countOf(dynamic v) {
    if (v is num) return v.toInt();
    if (v is List) return v.length;
    return 0;
  }
}

/// A payable student option in the payment-entry form.
class PayableStudent {
  const PayableStudent({
    required this.studentNumber,
    required this.firstName,
    required this.middleName,
    required this.lastName,
    required this.course,
    required this.major,
    required this.yearLevel,
    required this.semester,
    required this.sy,
  });

  final String studentNumber;
  final String firstName;
  final String middleName;
  final String lastName;
  final String course;
  final String major;
  final String yearLevel;
  final String semester;
  final String sy;

  String get fullName =>
      '$lastName, $firstName${middleName.isNotEmpty ? ' $middleName' : ''}'
          .trim();

  factory PayableStudent.fromJson(Map<String, dynamic> j) => PayableStudent(
        studentNumber: (j['student_number'] ?? '').toString(),
        firstName: (j['first_name'] ?? '').toString(),
        middleName: (j['middle_name'] ?? '').toString(),
        lastName: (j['last_name'] ?? '').toString(),
        course: (j['course'] ?? '').toString(),
        major: (j['major'] ?? '').toString(),
        yearLevel: (j['year_level'] ?? '').toString(),
        semester: (j['semester'] ?? '').toString(),
        sy: (j['sy'] ?? '').toString(),
      );
}

/// Fee template from the `fees` table — powers the description picker and
/// the Fees Setup screen (web: Accounting::course_setUp).
class FeeTemplate {
  const FeeTemplate({
    required this.id,
    required this.type,
    required this.description,
    required this.amount,
  });

  final int id;
  final String type;
  final String description;
  final double amount;

  factory FeeTemplate.fromJson(Map<String, dynamic> j) => FeeTemplate(
        id: (j['id'] as num?)?.toInt() ?? 0,
        type: (j['type'] ?? '').toString(),
        description: (j['description'] ?? '').toString(),
        amount: (j['amount'] as num?)?.toDouble() ?? 0,
      );
}

/// A recorded payment row (paymentsaccounts + joined student name).
class PaymentEntry {
  const PaymentEntry({
    required this.id,
    required this.date,
    required this.time,
    required this.orNumber,
    required this.studentNumber,
    required this.studentName,
    required this.amount,
    this.fullAmount,
    required this.description,
    required this.paymentType,
    required this.cashier,
    required this.sem,
    required this.sy,
  });

  final int id;
  final String date;
  final String time;
  final String orNumber;
  final String studentNumber;
  final String studentName;
  final double amount;
  final double? fullAmount;
  final String description;
  final String paymentType;
  final String cashier;
  final String sem;
  final String sy;

  factory PaymentEntry.fromJson(Map<String, dynamic> j) {
    double money(dynamic v) => (v is num) ? v.toDouble() : double.tryParse('$v') ?? 0;
    return PaymentEntry(
      id: (j['id'] as num?)?.toInt() ?? (j['ID'] as num?)?.toInt() ?? 0,
      date: (j['date'] ?? j['PDate'] ?? '').toString(),
      time: (j['time'] ?? j['pTime'] ?? '').toString(),
      orNumber: (j['or_number'] ?? j['ORNumber'] ?? '').toString(),
      studentNumber: (j['student_number'] ?? j['StudentNumber'] ?? '').toString(),
      studentName: (j['student_name'] ?? j['StudentName'] ?? '').toString(),
      amount: money(j['amount'] ?? j['Amount']),
      fullAmount: j['full_amount'] == null ? null : money(j['full_amount']),
      description: (j['description'] ?? '').toString(),
      paymentType: (j['payment_type'] ?? j['PaymentType'] ?? '').toString(),
      cashier: (j['cashier'] ?? j['Cashier'] ?? '').toString(),
      sem: (j['sem'] ?? j['Sem'] ?? '').toString(),
      sy: (j['sy'] ?? j['SY'] ?? '').toString(),
    );
  }
}

/// Payment audit entry (payment_audit_log) — web: Accounting::paymentAuditLog.
class PaymentAuditEntry {
  const PaymentAuditEntry({
    required this.id,
    required this.changedAt,
    required this.action,
    required this.orNumber,
    required this.studentNumber,
    required this.studentName,
    required this.description,
    required this.amount,
    required this.changedBy,
  });

  final int id;
  final String changedAt;
  final String action;
  final String orNumber;
  final String studentNumber;
  final String studentName;
  final String description;
  final double amount;
  final String changedBy;

  factory PaymentAuditEntry.fromJson(Map<String, dynamic> j) =>
      PaymentAuditEntry(
        id: (j['id'] as num?)?.toInt() ?? 0,
        changedAt: (j['changed_at'] ?? '').toString(),
        action: (j['action'] ?? '').toString(),
        orNumber: (j['or_number'] ?? '').toString(),
        studentNumber: (j['student_number'] ?? '').toString(),
        studentName: (j['student_name'] ?? '').toString(),
        description: (j['description'] ?? '').toString(),
        amount: (j['amount'] as num?)?.toDouble() ?? 0,
        changedBy: (j['changed_by'] ?? '').toString(),
      );
}

/// A (student, fee) pair paid below full price — web partialPayments.
class PartialPaymentRow {
  const PartialPaymentRow({
    required this.studentNumber,
    required this.studentName,
    required this.description,
    required this.fullAmount,
    required this.paidAmount,
    required this.outstanding,
    required this.lastPaymentDate,
  });

  final String studentNumber;
  final String studentName;
  final String description;
  final double fullAmount;
  final double paidAmount;
  final double outstanding;
  final String lastPaymentDate;

  factory PartialPaymentRow.fromJson(Map<String, dynamic> j) =>
      PartialPaymentRow(
        studentNumber: (j['student_number'] ?? '').toString(),
        studentName: (j['student_name'] ?? '').toString(),
        description: (j['description'] ?? '').toString(),
        fullAmount: (j['full_amount'] as num?)?.toDouble() ?? 0,
        paidAmount: (j['paid_amount'] as num?)?.toDouble() ?? 0,
        outstanding: (j['outstanding'] as num?)?.toDouble() ?? 0,
        lastPaymentDate: (j['last_payment_date'] ?? '').toString(),
      );
}

/// Ledger row — income (collection) or expense with running balance.
class LedgerRow {
  const LedgerRow({
    required this.date,
    required this.time,
    required this.type,
    required this.description,
    required this.ref,
    required this.amount,
    required this.balance,
  });

  final String date;
  final String time;
  final String type; // 'income' | 'expense'
  final String description;
  final String ref;
  final double amount;
  final double balance;

  bool get isIncome => type == 'income';

  factory LedgerRow.fromJson(Map<String, dynamic> j) => LedgerRow(
        date: (j['date'] ?? '').toString(),
        time: (j['time'] ?? '').toString(),
        type: (j['type'] ?? '').toString(),
        description: (j['description'] ?? '').toString(),
        ref: (j['ref'] ?? '').toString(),
        amount: (j['amount'] as num?)?.toDouble() ?? 0,
        balance: (j['balance'] as num?)?.toDouble() ?? 0,
      );
}

/// Filtered expense row — web: Accounting::expenseSGenerate.
class ExpenseReportRow {
  const ExpenseReportRow({
    required this.id,
    required this.description,
    required this.responsible,
    required this.date,
    required this.category,
    required this.amount,
  });

  final int id;
  final String description;
  final String responsible;
  final String date;
  final String category;
  final double amount;

  factory ExpenseReportRow.fromJson(Map<String, dynamic> j) =>
      ExpenseReportRow(
        id: (j['id'] as num?)?.toInt() ?? 0,
        description: (j['description'] ?? '').toString(),
        responsible: (j['responsible'] ?? '').toString(),
        date: (j['date'] ?? '').toString(),
        category: (j['category'] ?? '').toString(),
        amount: (j['amount'] as num?)?.toDouble() ?? 0,
      );
}

/// Term option for the collection-report filter ("Sem|SY").
class TermOption {
  const TermOption({required this.sem, required this.sy, required this.label});

  final String sem;
  final String sy;
  final String label;

  String get value => '$sem|$sy';

  factory TermOption.fromJson(Map<String, dynamic> j) => TermOption(
        sem: (j['sem'] ?? '').toString(),
        sy: (j['sy'] ?? '').toString(),
        label: (j['label'] ?? '').toString(),
      );
}
