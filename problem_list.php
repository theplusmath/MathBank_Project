<?php
// problem_list.php
$conn = new mysqli("localhost", "theplusmath", "wnstj1205+", "theplusmath");
$conn->set_charset("utf8mb4");

$result = $conn->query("SELECT id, title, path, difficulty, type, category, source, created_at FROM problems ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>문제 목록</title>
  <style>
    body {
      font-family: 'Malgun Gothic', sans-serif;
      max-width: 1100px;
      margin: 30px auto;
      padding: 20px;
    }
    table {
      border-collapse: collapse;
      width: 100%;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 6px 8px;
      text-align: left;
      font-size: 14px;
    }
    th {
      background-color: #f5f5f5;
    }
    td.difficulty {
      text-align: center;
      width: 30px;
    }
    td.type, td.category {
      width: 80px;
    }
    td.source {
      min-width: 160px;
    }
    td.path {
      max-width: 250px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    td.created {
      width: 100px;
    }
    .actions {
      white-space: nowrap;
    }
  </style>
</head>
<body>
  <h2>📋 등록된 문제 목록</h2>
  <table>
    <thead>
      <tr>
        <th>제목</th>
        <th class="path">경로</th>
        <th class="difficulty">난이도</th>
        <th class="type">유형</th>
        <th class="category">분류</th>
        <th class="source">출처</th>
        <th class="created">등록일</th>
        <th>관리</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()) : ?>
        <tr>
          <td><?= htmlspecialchars($row['title']) ?></td>
          <td class="path">
            <?php
              $parts = explode("~", $row['path']);
              if (count($parts) > 5) {
                echo htmlspecialchars($parts[0] . '~' . $parts[1] . '~⋯~' . end($parts));
              } else {
                echo htmlspecialchars($row['path']);
              }
            ?>
          </td>
          <td class="difficulty"><?= htmlspecialchars($row['difficulty']) ?></td>
          <td class="type"><?= htmlspecialchars($row['type']) ?></td>
          <td class="category"><?= htmlspecialchars($row['category']) ?></td>
          <td class="source"><?= htmlspecialchars($row['source']) ?></td>
          <td class="created"><?= date("Y-m-d", strtotime($row['created_at'])) ?></td>
          <td class="actions">
            <a href="problem_solve.php?id=<?= $row['id'] ?>"><button>풀이</button></a>
            <a href="edit_problem.php?id=<?= $row['id'] ?>"><button>수정</button></a>
            <button onclick="alert('삭제 기능은 추후 제공됩니다.')">삭제</button>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</body>
</html>
