/// Announcement row from `GET /api/mobile/announcements`.
class Announcement {
  const Announcement({
    required this.id,
    required this.title,
    required this.message,
    required this.author,
    required this.audience,
    required this.datePosted,
    required this.dateExpire,
    required this.imageUrl,
  });

  final int id;
  final String title;
  final String message;
  final String author;
  final String audience;
  final String datePosted;
  final String dateExpire;
  final String imageUrl;

  factory Announcement.fromJson(Map<String, dynamic> j) => Announcement(
        id: (j['id'] as num?)?.toInt() ?? 0,
        title: (j['title'] ?? '').toString(),
        message: (j['message'] ?? '').toString(),
        author: (j['author'] ?? '').toString(),
        audience: (j['audience'] ?? '').toString(),
        datePosted: (j['date_posted'] ?? '').toString(),
        dateExpire: (j['date_expire'] ?? '').toString(),
        imageUrl: (j['image_url'] ?? '').toString(),
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'title': title,
        'message': message,
        'author': author,
        'audience': audience,
        'date_posted': datePosted,
        'date_expire': dateExpire,
        'image_url': imageUrl,
      };
}

/// Note row from `GET /api/mobile/notes`.
class Note {
  const Note({
    required this.id,
    required this.title,
    required this.content,
    required this.createdAt,
    required this.updatedAt,
  });

  final int id;
  final String title;
  final String content;
  final String createdAt;
  final String updatedAt;

  factory Note.fromJson(Map<String, dynamic> j) => Note(
        id: (j['id'] as num?)?.toInt() ?? 0,
        title: (j['title'] ?? '').toString(),
        content: (j['content'] ?? '').toString(),
        createdAt: (j['created_at'] ?? '').toString(),
        updatedAt: (j['updated_at'] ?? '').toString(),
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'title': title,
        'content': content,
        'created_at': createdAt,
        'updated_at': updatedAt,
      };
}

/// Todo row from `GET /api/mobile/todos`.
class Todo {
  const Todo({
    required this.id,
    required this.task,
    required this.isDone,
    required this.dueDate,
    required this.createdAt,
    required this.completedAt,
    required this.comment,
  });

  final int id;
  final String task;
  final bool isDone;
  final String dueDate;
  final String createdAt;
  final String completedAt;
  final String comment;

  factory Todo.fromJson(Map<String, dynamic> j) => Todo(
        id: (j['id'] as num?)?.toInt() ?? 0,
        task: (j['task'] ?? '').toString(),
        isDone: j['is_done'] == true,
        dueDate: (j['due_date'] ?? '').toString(),
        createdAt: (j['created_at'] ?? '').toString(),
        completedAt: (j['completed_at'] ?? '').toString(),
        comment: (j['comment'] ?? '').toString(),
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'task': task,
        'is_done': isDone,
        'due_date': dueDate,
        'created_at': createdAt,
        'completed_at': completedAt,
        'comment': comment,
      };
}

/// Personnel directory entry from `GET /api/mobile/personnel`.
class Personnel {
  const Personnel({
    required this.id,
    required this.fullName,
    required this.title,
    required this.bio,
    required this.photoUrl,
    required this.sortOrder,
  });

  final int id;
  final String fullName;
  final String title;
  final String bio;
  final String photoUrl;
  final int sortOrder;

  factory Personnel.fromJson(Map<String, dynamic> j) => Personnel(
        id: (j['id'] as num?)?.toInt() ?? 0,
        fullName: (j['full_name'] ?? '').toString(),
        title: (j['title'] ?? '').toString(),
        bio: (j['bio'] ?? '').toString(),
        photoUrl: (j['photo_url'] ?? '').toString(),
        sortOrder: (j['sort_order'] as num?)?.toInt() ?? 100,
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'full_name': fullName,
        'title': title,
        'bio': bio,
        'photo_url': photoUrl,
        'sort_order': sortOrder,
      };
}
